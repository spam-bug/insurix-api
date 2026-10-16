<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
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
        $this->configureRateLimiting();
    }

    /**
     * Public lead form submissions arrive through the Next.js server, so every request shares
     * its IP. The visitor's own IP comes in X-Client-IP; it can be spoofed, so the hourly cap
     * per form is the real backstop.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('lead-form', function (Request $request) {
            $token = (string) $request->route('token');
            $visitor = $request->header('X-Client-IP') ?: $request->ip();

            return [
                Limit::perMinute(5)->by("lead-form:{$token}:{$visitor}"),
                Limit::perHour(60)->by("lead-form:{$token}"),
            ];
        });
    }
}
