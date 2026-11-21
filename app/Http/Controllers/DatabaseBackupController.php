<?php

namespace App\Http\Controllers;

use App\Helpers\UserLogHelper;
use App\Models\UserLog;
use App\Services\AutomaticBackupService;
use App\Services\DatabaseBackupService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Sauvegarde de la base depuis l'application (SUPER IT, permission exporter-base-donnees).
 * Le fichier .sql.gz est compressé et envoyé au fil de l'eau : il n'est jamais écrit sur le serveur.
 */
class DatabaseBackupController extends Controller
{
    /** Délai minimal entre deux exports (secondes), pour ménager le serveur. */
    private const DELAI_ENTRE_EXPORTS = 600;

    private const ACTION_JOURNAL = 'Export_base_donnees';

    /** Durée de validité de la confirmation du mot de passe pour télécharger (secondes). */
    private const CONFIRMATION_VALIDE = 900;

    public function index()
    {
        Gate::authorize('exporter-base-donnees');

        $derniers = UserLog::with('user:id,name,postnom')
            ->where('action', self::ACTION_JOURNAL)
            ->latest()
            ->limit(10)
            ->get();

        $auto = app(AutomaticBackupService::class);
        $age = $auto->ageDerniereHeures();

        return view('admin.sauvegarde-base', [
            'derniers' => $derniers,
            'attente' => RateLimiter::availableIn($this->cle()),
            'bloque' => RateLimiter::tooManyAttempts($this->cle(), 1),
            // Sauvegardes automatiques conservées sur le serveur
            'automatiques' => $auto->liste(),
            'ageDerniere' => $age,
            'alerte' => $age === null || $age > config('sauvegarde.alerte_apres_heures', 26),
            'retention' => config('sauvegarde.retention_jours', 7),
            'cronActif' => (string) config('sauvegarde.jeton_cron') !== '',
            'creationBloquee' => RateLimiter::tooManyAttempts('sauvegarde-serveur', 1),
        ]);
    }

    /** Télécharge une sauvegarde automatique conservée sur le serveur. */
    public function downloadFile(string $nom, AutomaticBackupService $auto)
    {
        Gate::authorize('exporter-base-donnees');

        if (! $this->confirmationRecente()) {
            return redirect()->route('sauvegarde.index');
        }

        $chemin = $auto->chemin($nom);
        abort_if($chemin === null, 404);

        UserLogHelper::log_user_activity(self::ACTION_JOURNAL, "Téléchargement de la sauvegarde automatique {$nom}");

        return response()->download($chemin, $nom, ['Content-Type' => 'application/gzip']);
    }

    /** Crée tout de suite une sauvegarde sur le serveur (même mécanisme que le cron). */
    public function createNow(AutomaticBackupService $auto)
    {
        Gate::authorize('exporter-base-donnees');

        if (RateLimiter::tooManyAttempts('sauvegarde-serveur', 1)) {
            return redirect()->route('sauvegarde.index')
                ->with('error', 'Une sauvegarde sur le serveur vient d\'être créée. Réessayez dans quelques minutes.');
        }
        RateLimiter::hit('sauvegarde-serveur', self::DELAI_ENTRE_EXPORTS);

        try {
            $resultat = $auto->creer('manuelle par ' . auth()->user()->name);
        } catch (Throwable $e) {
            return redirect()->route('sauvegarde.index')->with('error', 'Échec de la sauvegarde : ' . $e->getMessage());
        }

        UserLogHelper::log_user_activity(self::ACTION_JOURNAL, "Sauvegarde créée sur le serveur : {$resultat['nom']}");

        return redirect()->route('sauvegarde.index')->with('success', sprintf(
            'Sauvegarde créée sur le serveur : %s (%s Ko, %.1f s).',
            $resultat['nom'], number_format($resultat['taille'] / 1024, 0, ',', ' '), $resultat['duree']
        ));
    }

    /** Mot de passe confirmé il y a moins de 15 minutes ; sinon la confirmation est effacée. */
    private function confirmationRecente(): bool
    {
        if (time() - (int) session('auth.password_confirmed_at', 0) > self::CONFIRMATION_VALIDE) {
            session()->forget('auth.password_confirmed_at');

            return false;
        }

        return true;
    }

    public function download(DatabaseBackupService $service)
    {
        Gate::authorize('exporter-base-donnees');

        // Mot de passe confirmé il y a moins de 15 minutes, sinon retour à la page qui le redemande
        if (! $this->confirmationRecente()) {
            return redirect()->route('sauvegarde.index');
        }

        if (RateLimiter::tooManyAttempts($this->cle(), 1)) {
            $minutes = (int) ceil(RateLimiter::availableIn($this->cle()) / 60);

            return redirect()->route('sauvegarde.index')
                ->with('error', "Une sauvegarde vient d'être faite. Réessayez dans {$minutes} minute(s).");
        }
        RateLimiter::hit($this->cle(), self::DELAI_ENTRE_EXPORTS);

        $fichier = 'sauvegarde_' . config('database.connections.mysql.database') . '_' . now()->format('Y-m-d_H-i') . '.sql.gz';

        return new StreamedResponse(function () use ($service, $fichier) {
            @set_time_limit(0);
            $debut = microtime(true);
            $octets = 0;
            $gzip = deflate_init(ZLIB_ENCODING_GZIP, ['level' => 6]);

            $ecrire = function (string $sql) use ($gzip, &$octets) {
                $morceau = deflate_add($gzip, $sql, ZLIB_NO_FLUSH);
                if ($morceau !== '') {
                    $octets += strlen($morceau);
                    echo $morceau;
                    flush();
                }
            };

            try {
                $stats = $service->dump($ecrire);
                $fin = deflate_add($gzip, '', ZLIB_FINISH);
                $octets += strlen($fin);
                echo $fin;
                flush();

                UserLogHelper::log_user_activity(
                    self::ACTION_JOURNAL,
                    sprintf('Sauvegarde %s : %d tables, %d lignes, %s Ko, %.1f s',
                        $fichier, $stats['tables'], $stats['rows'], number_format($octets / 1024, 0, ',', ' '), microtime(true) - $debut)
                );
            } catch (Throwable $e) {
                report($e);
                UserLogHelper::log_user_activity(self::ACTION_JOURNAL, "ÉCHEC de la sauvegarde {$fichier} : {$e->getMessage()}");
                // Le fichier reçu sera incomplet : la fin est marquée pour qu'il ne soit pas pris pour une sauvegarde valide
                echo deflate_add($gzip, "\n-- ERREUR : SAUVEGARDE INCOMPLÈTE\n", ZLIB_FINISH);
            }
        }, 200, [
            'Content-Type' => 'application/gzip',
            'Content-Disposition' => 'attachment; filename="' . $fichier . '"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function cle(): string
    {
        return 'sauvegarde-base';
    }
}
