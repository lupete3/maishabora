<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
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
}
