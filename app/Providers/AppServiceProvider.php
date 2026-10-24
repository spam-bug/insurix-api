<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Models\Workspace;
use App\Policies\NotificationPolicy;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
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
        Model::preventLazyLoading(! $this->app->isProduction());

        $this->configureRateLimiting();

        // Bound for every route, not only those whose controller asks for it, so the membership
        // middleware always gets the model and scoped child bindings have their parent.
        Route::model('workspace', Workspace::class);

        // Laravel's own notification model, so its policy isn't found by naming convention.
        Gate::policy(DatabaseNotification::class, NotificationPolicy::class);

        // Reset links open the frontend's reset page, which posts the token back to the API.
        ResetPassword::createUrlUsing(fn (User $user, string $token) => config('app.frontend_url').'/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ]));
    }

    /**
     * Visitors post the public lead form straight from their browser, so each one is limited by
     * their own IP, with an hourly cap per form as the backstop.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('lead-form', function (Request $request) {
            $token = $request->route()->originalParameter('leadForm');

            return [
                Limit::perMinute(5)->by("lead-form:{$token}:{$request->ip()}"),
                Limit::perHour(60)->by("lead-form:{$token}"),
            ];
        });
    }
}
