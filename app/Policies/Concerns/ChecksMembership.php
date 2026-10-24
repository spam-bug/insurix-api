<?php

declare(strict_types=1);

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksMembership
{
    /** Whether the user belongs to the workspace that owns the record. */
    protected function isMember(User $user, string $workspaceId): bool
    {
        return $user->workspaces()->whereKey($workspaceId)->exists();
    }
}
