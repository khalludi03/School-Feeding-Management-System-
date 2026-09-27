<?php

namespace App\Providers;

use Illuminate\Support\Facades\Auth;
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
        try {
            Auth::guard('web')->setRememberDuration(14 * 24 * 60);
        } catch (\Exception $e) {
            // Ignore missing app key during initial setup (e.g. key:generate)
        }
    }
}
