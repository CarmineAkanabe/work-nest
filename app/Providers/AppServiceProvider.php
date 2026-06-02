<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Events\TaskCompleted;
use App\Listeners\LogTaskCompletion;
use App\Listeners\NotifyProjectManager;
use Event;
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
        // Authorization policies (Gates)
        Gate::define('is_admin', function ($user) {
            return $user->role === UserRole::Admin;
        });

        Gate::define('is_manager', function ($user) {
            return $user->role === UserRole::Manager;
        });

        // Event and listeners
        Event::listen(TaskCompleted::class, NotifyProjectManager::class);
        Event::listen(TaskCompleted::class, LogTaskCompletion::class);
    }
}
