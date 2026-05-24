<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

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
        Gate::define('is_admin', function ($user) {
            return $user->role === UserRole::Admin;
        });

        Gate::define('is_manager', function ($user) {
            return $user->role === UserRole::Manager;
        });
    }
}
