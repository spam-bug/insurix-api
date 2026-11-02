<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Staff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** The console needs two-factor: until it's set up, a staff member can only set it up. */
class EnsureStaffTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Staff $staff */
        $staff = $request->user('staff');

        if (! $staff->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('staff.two-factor.create');
        }

        return $next($request);
    }
}
