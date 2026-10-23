<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Str;

class WorkspaceInvitationPolicy
{
    /** Only the invited email can see or accept an invite, so a forwarded link is no use to anyone else. */
    public function accept(User $user, WorkspaceInvitation $invitation): bool
    {
        return Str::lower($user->email) === Str::lower($invitation->email);
    }
}
