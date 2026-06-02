<?php

namespace App\Providers;

use App\Enums\UserRole;
use App\Events\TaskCompleted;
use App\Listeners\LogTaskCompletion;
use App\Listeners\NotifyProjectManager;
use Event;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Rate Limiting on all roles except Admin
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();

            if (!$user) {
                return Limit::perMinute(10)->by($request->ip());
            }

            return match ($user->role) {
                UserRole::Admin => Limit::none(),
                UserRole::Manager  => Limit::perMinute(60)->by($user->id),
                UserRole::Employee => Limit::perMinute(30)->by($user->id)
            };
        });
    }
}
