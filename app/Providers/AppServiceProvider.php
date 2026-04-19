<?php

namespace App\Providers;

use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use Livewire\Blaze\Blaze;
use Illuminate\Support\ServiceProvider;

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

        if (\Illuminate\Support\Facades\Schema::hasTable('company_informations')) {
            \Illuminate\Support\Facades\View::share('company', \App\Models\CompanyInformation::getActive());
        }
    }
}
