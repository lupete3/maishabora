<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\Blaze;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);

        Blaze::optimize()->in(resource_path('views/components'));

        $this->registerDateQueryMacros();

        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            View::share('company', Cache::remember(
                'company_information.active',
                now()->addHours(12),
                fn () => \App\Models\CompanyInformation::getActive()
            ));
        } catch (Throwable) {
            View::share('company', null);
        }
    }

    /**
     * Filtres par jour qui utilisent les index.
     *
     * whereDate('created_at', $jour) fait calculer DATE(created_at) sur chaque ligne :
     * MySQL ne peut pas utiliser l'index de la colonne. Ces raccourcis donnent le même
     * résultat avec une plage horaire, que l'index sait parcourir.
     *   ->whereOnDate('created_at', $jour)    même jour (00:00:00 à 23:59:59)
     *   ->whereFromDate('created_at', $jour)  à partir de ce jour
     *   ->whereToDate('created_at', $jour)    jusqu'à ce jour inclus
     * Disponibles sur les requêtes Eloquent, les relations et DB::table().
     */
    private function registerDateQueryMacros(): void
    {
        QueryBuilder::macro('whereOnDate', function (string $column, $date) {
            $day = Carbon::parse($date);

            return $this->whereBetween($column, [$day->copy()->startOfDay(), $day->copy()->endOfDay()]);
        });

        QueryBuilder::macro('whereFromDate', function (string $column, $date) {
            return $this->where($column, '>=', Carbon::parse($date)->startOfDay());
        });

        QueryBuilder::macro('whereToDate', function (string $column, $date) {
            return $this->where($column, '<=', Carbon::parse($date)->endOfDay());
        });
    }
}
