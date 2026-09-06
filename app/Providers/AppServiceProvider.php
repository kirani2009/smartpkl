<?php

namespace App\Providers;

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
     *
     * Catatan: Rate limiter untuk middleware 'throttle:login' dan
     * 'throttle:register' didefinisikan hanya di RouteServiceProvider.
     * Mendefinisikannya dua kali (di sini dan di RouteServiceProvider)
     * akan menimpa definisi secara tidak deterministik dan menyebabkan
     * perilaku login/register yang tidak konsisten.
     */
    public function boot(): void
    {
        //
    }
}
