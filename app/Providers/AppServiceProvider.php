<?php

namespace App\Providers;

use App\Enums\UserRole;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        \App\Models\Spj::observe(\App\Observers\SpjObserver::class);
        \App\Models\Aset::observe(\App\Observers\AsetObserver::class);

        // Super Admin has full unrestricted access across policies and gates
        Gate::before(function ($user, string $ability) {
            if ($user->role === UserRole::SUPER_ADMIN || $user->hasRole(UserRole::SUPER_ADMIN->value)) {
                return true;
            }

            return null;
        });
    }
}
