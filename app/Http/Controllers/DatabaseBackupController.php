<?php

namespace App\Http\Controllers;

use App\Helpers\UserLogHelper;
use App\Models\UserLog;
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

    public function index()
    {
        Gate::authorize('exporter-base-donnees');

        $derniers = UserLog::with('user:id,name,postnom')
            ->where('action', self::ACTION_JOURNAL)
            ->latest()
            ->limit(10)
            ->get();

        return view('admin.sauvegarde-base', [
            'derniers' => $derniers,
            'attente' => RateLimiter::availableIn($this->cle()),
            'bloque' => RateLimiter::tooManyAttempts($this->cle(), 1),
        ]);
    }

    public function download(DatabaseBackupService $service)
    {
        Gate::authorize('exporter-base-donnees');

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
