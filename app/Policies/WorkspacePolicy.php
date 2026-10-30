<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Policies\Concerns\ChecksMembership;

class WorkspacePolicy
{
    use ChecksMembership;

    /** Anyone signed in can start a workspace of their own. */
    public function create(User $user): bool
    {
        return true;
    }

    /** Members work with everything in the workspace. */
    public function view(User $user, Workspace $workspace): bool
    {
        return $this->isMember($user, $workspace->id);
    }

    /** Only the owner changes the workspace itself: settings, logo, members and invites. */
    public function manage(User $user, Workspace $workspace): bool
    {
        return $user->workspaces()
            ->whereKey($workspace->id)
            ->wherePivot('role', WorkspaceRole::Owner)
            ->exists();
    }

    /**
     * Only the owner brings back a deleted workspace. Read from the members table, since the
     * user's workspaces leave out deleted ones.
     */
    public function restore(User $user, Workspace $workspace): bool
    {
        return $workspace->members()
            ->where('user_id', $user->id)
            ->where('role', WorkspaceRole::Owner)
            ->exists();
    }
}
