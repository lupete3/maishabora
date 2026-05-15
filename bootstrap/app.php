<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserRole;
use App\Models\SystemLog;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

use Illuminate\Session\TokenMismatchException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'check.user' => \App\Http\Middleware\CheckUserStatusAndRole::class,
        ]);

        $middleware->appendToGroup('web', \App\Http\Middleware\CheckUserStatusAndRole::class);
    })
    ->withSchedule(function (Schedule $schedule) {
        // Notification retard paiement
        $schedule->command('check:overdue-repayments')->dailyAt('08:00');
        // Rapport mensuel automatisé
        $schedule->command('reports:monthly-contribution')->monthly();
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->report(function (Throwable $e) {
            try {
                SystemLog::create([
                    'level' => 'error',
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            } catch (\Exception $inner) {
                // éviter les erreurs si la base n’est pas accessible
            }
        });

        $exceptions->render(function (TokenMismatchException $e, $request) {
            return redirect()->route('login');
        });

        // tu peux aussi définir render() si tu veux changer la vue d’erreur
        $exceptions->render(function (Throwable $e, $request) {
            // par exemple afficher une vue d’erreur générique
            // return response()->view('errors.500', [], 500);
        });
    })->create();
