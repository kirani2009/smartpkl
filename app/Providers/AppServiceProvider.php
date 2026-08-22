<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
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
        // Batasi percobaan login untuk mencegah brute force (docs/ai/SECURITY.json).
        RateLimiter::for('login', function () {
            return Limit::perMinute(5)->by(request()->ip());
        });

        // Batasi registrasi publik untuk mencegah pembuatan akun massal.
        RateLimiter::for('register', function () {
            return Limit::perMinute(10)->by(request()->ip());
        });
    }
}
