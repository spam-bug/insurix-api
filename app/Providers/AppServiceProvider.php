<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Only Fortify's two-factor building blocks are used; the API has its own auth routes.
        Fortify::ignoreRoutes();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        // Reset links open the frontend's reset page, which posts the token back to the API.
        ResetPassword::createUrlUsing(fn (User $user, string $token) => config('app.frontend_url').'/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));
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
