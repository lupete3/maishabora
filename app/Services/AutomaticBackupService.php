<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\SystemLog;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Throwable;

/**
 * Sauvegardes automatiques rangées sur le serveur (storage/app/private/backups).
 *
 *  - même export que le bouton (DatabaseBackupService) ;
 *  - écriture dans un fichier « .part », vérification complète, puis renommage :
 *    une sauvegarde incomplète n'est jamais conservée comme si elle était bonne ;
 *  - conservation de N jours (la plus récente est toujours gardée) ;
 *  - échec : Logs du système + notification aux comptes SUPER IT.
 */
class AutomaticBackupService
{
    /** Nom de fichier accepté (protection contre ../ et autres chemins) */
    private const MOTIF_NOM = '/^sauvegarde_[A-Za-z0-9_\-]+\.sql\.gz$/';

    public function __construct(private DatabaseBackupService $dumper)
    {
    }

    public function dossier(): string
    {
        $dossier = storage_path('app/private/' . config('sauvegarde.dossier', 'backups'));

        if (! is_dir($dossier)) {
            mkdir($dossier, 0750, true);
        }

        return $dossier;
    }

    /**
     * Sauvegardes conservées, de la plus récente à la plus ancienne.
     *
     * @return array<int, array{nom: string, chemin: string, taille: int, date: Carbon}>
     */
    public function liste(): array
    {
        $fichiers = [];

        foreach (glob($this->dossier() . DIRECTORY_SEPARATOR . 'sauvegarde_*.sql.gz') ?: [] as $chemin) {
            $fichiers[] = [
                'nom'    => basename($chemin),
                'chemin' => $chemin,
                'taille' => filesize($chemin),
                'date'   => Carbon::createFromTimestamp(filemtime($chemin)),
            ];
        }

        usort($fichiers, fn ($a, $b) => $b['date'] <=> $a['date']);

        return $fichiers;
    }

    public function derniere(): ?array
    {
        return $this->liste()[0] ?? null;
    }

    /** Âge de la dernière sauvegarde en heures (null s'il n'y en a aucune). */
    public function ageDerniereHeures(): ?float
    {
        $derniere = $this->derniere();

        return $derniere ? $derniere['date']->diffInMinutes(now()) / 60 : null;
    }

    /** Vrai si aucune sauvegarde récente n'existe (intervalle configuré). */
    public function estNecessaire(): bool
    {
        $age = $this->ageDerniereHeures();

        return $age === null || $age >= config('sauvegarde.intervalle_heures', 20);
    }

    /**
     * Crée une sauvegarde sur le serveur.
     *
     * @param string $origine qui l'a demandée (cron, utilisateur…), pour le journal
     * @return array{nom: string, taille: int, tables: int, lignes: int, duree: float, supprimees: int}
     */
    public function creer(string $origine): array
    {
        // Une seule sauvegarde à la fois (deux appels cron rapprochés, ou cron + bouton)
        $verrou = Cache::lock('sauvegarde-automatique', 900);
        if (! $verrou->get()) {
            throw new RuntimeException('Une sauvegarde est déjà en cours.');
        }

        $dossier = $this->dossier();
        $nom = 'sauvegarde_' . config('database.connections.mysql.database') . '_' . now()->format('Y-m-d_H-i') . '.sql.gz';
        $final = $dossier . DIRECTORY_SEPARATOR . $nom;
        $partiel = $final . '.part';
        $debut = microtime(true);

        try {
            $libreMo = disk_free_space($dossier) / 1048576;
            if ($libreMo < config('sauvegarde.espace_minimal_mo', 200)) {
                throw new RuntimeException(sprintf('Espace disque insuffisant : %d Mo libres.', $libreMo));
            }

            $gz = gzopen($partiel, 'wb6');
            if ($gz === false) {
                throw new RuntimeException("Impossible de créer le fichier {$partiel}.");
            }
            $stats = $this->dumper->dump(fn (string $sql) => gzwrite($gz, $sql));
            gzclose($gz);

            $this->verifier($partiel);
            rename($partiel, $final);

            $supprimees = $this->nettoyer();
            $resultat = [
                'nom' => $nom,
                'taille' => filesize($final),
                'tables' => $stats['tables'],
                'lignes' => $stats['rows'],
                'duree' => round(microtime(true) - $debut, 1),
                'supprimees' => $supprimees,
            ];

            SystemLog::create([
                'level' => 'info',
                'message' => sprintf('Sauvegarde automatique (%s) : %s, %d tables, %d lignes, %s Ko, %.1f s, %d ancienne(s) supprimée(s)',
                    $origine, $nom, $resultat['tables'], $resultat['lignes'],
                    number_format($resultat['taille'] / 1024, 0, ',', ' '), $resultat['duree'], $supprimees),
            ]);

            return $resultat;
        } catch (Throwable $e) {
            if (is_file($partiel)) {
                @unlink($partiel);
            }
            $this->signalerEchec($origine, $e);

            throw $e;
        } finally {
            $verrou->release();
        }
    }

    /**
     * Relit le fichier compressé jusqu'au bout : gzip lisible et fin de sauvegarde présente.
     */
    private function verifier(string $chemin): void
    {
        $gz = gzopen($chemin, 'rb');
        if ($gz === false) {
            throw new RuntimeException('Sauvegarde illisible.');
        }

        $fin = '';
        while (! gzeof($gz)) {
            $morceau = gzread($gz, 1 << 20);
            if ($morceau === false) {
                gzclose($gz);
                throw new RuntimeException('Sauvegarde corrompue (lecture gzip impossible).');
            }
            $fin = substr($fin . $morceau, -200);
        }
        gzclose($gz);

        if (! str_contains($fin, '-- Fin de la sauvegarde') || str_contains($fin, 'SAUVEGARDE INCOMPLÈTE')) {
            throw new RuntimeException('Sauvegarde incomplète : la fin du fichier est absente.');
        }
    }

    /**
     * Supprime les sauvegardes plus anciennes que la durée de conservation,
     * en gardant toujours la plus récente.
     */
    public function nettoyer(): int
    {
        $limite = now()->subDays(config('sauvegarde.retention_jours', 7));
        $supprimees = 0;

        foreach (array_slice($this->liste(), 1) as $fichier) {
            if ($fichier['date']->lt($limite)) {
                @unlink($fichier['chemin']);
                $supprimees++;
            }
        }

        // Restes d'une sauvegarde interrompue
        foreach (glob($this->dossier() . DIRECTORY_SEPARATOR . '*.part') ?: [] as $partiel) {
            if (filemtime($partiel) < time() - 3600) {
                @unlink($partiel);
            }
        }

        return $supprimees;
    }

    /** Chemin d'une sauvegarde à partir de son nom, ou null si le nom est invalide ou absent. */
    public function chemin(string $nom): ?string
    {
        if (! preg_match(self::MOTIF_NOM, $nom)) {
            return null;
        }

        $chemin = $this->dossier() . DIRECTORY_SEPARATOR . $nom;

        return is_file($chemin) ? $chemin : null;
    }

    private function signalerEchec(string $origine, Throwable $e): void
    {
        try {
            SystemLog::create([
                'level' => 'error',
                'message' => "ÉCHEC de la sauvegarde automatique ({$origine}) : {$e->getMessage()}",
                'trace' => $e->getTraceAsString(),
            ]);

            foreach (User::role('SUPER IT')->get(['id']) as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'title' => 'Échec de la sauvegarde automatique',
                    'message' => "La sauvegarde automatique du " . now()->format('d/m/Y H:i') . " a échoué : {$e->getMessage()}",
                    'read' => false,
                ]);
            }
        } catch (Throwable) {
            // Le signalement ne doit jamais masquer l'erreur d'origine
        }
    }
}
