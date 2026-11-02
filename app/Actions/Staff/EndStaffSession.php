<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Facades\Auth;

class EndStaffSession
{
    /**
     * Signs out of the console only: the session gets a new id and token, but an agent signed
     * in to the app in the same browser stays signed in.
     */
    public function handle(Session $session): void
    {
        Auth::guard('staff')->logout();
        $session->migrate(true);
        $session->regenerateToken();
    }
}
