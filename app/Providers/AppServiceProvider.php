<?php

namespace App\Providers;

use App\Listeners\SendPasswordResetNotification;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        Event::listen(
            PasswordReset::class,
            SendPasswordResetNotification::class
        );

        Gate::define('access-admin-panel', fn($user) => $user->isAdmin() || $user->isTeacher());

        Gate::define('view-reports', fn($user) => $user->isAdmin());

        Gate::define('export-data', fn($user) => $user->isAdmin());
    }
}