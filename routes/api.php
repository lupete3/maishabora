<?php

use App\Http\Controllers\Api\V1\Client\AccountController;
use App\Http\Controllers\Api\V1\Client\AuthController;
use App\Http\Controllers\Api\V1\Client\PasswordController;
use App\Http\Controllers\Api\V1\Client\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API mobile — version 1
|--------------------------------------------------------------------------
| Toutes les routes de ce fichier sont préfixées par /api (voir
| bootstrap/app.php). Elles n'utilisent ni session ni cookie : le mobile
| s'identifie avec un jeton Sanctum envoyé dans l'en-tête
|   Authorization: Bearer <jeton>
*/

Route::prefix('v1/client')->name('api.v1.client.')->group(function () {

    // Public : obtenir un jeton
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login');

    // Protégé : jeton valide + compte membre toujours autorisé
    Route::middleware(['auth:sanctum', 'client.active'])->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout'])->name('logout');

        // Autorisé même avec un jeton limité : c'est la seule porte de sortie
        // d'un membre qui doit remplacer son code provisoire ou 1234.
        Route::put('password', [PasswordController::class, 'update'])
            ->middleware('throttle:5,1')
            ->name('password.update');

        // Données : interdites tant que le mot de passe n'a pas été changé
        Route::middleware('client.password')->group(function () {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::get('accounts', [AccountController::class, 'index'])->name('accounts.index');
            Route::get('transactions', [TransactionController::class, 'index'])->name('transactions.index');
        });
    });
});
