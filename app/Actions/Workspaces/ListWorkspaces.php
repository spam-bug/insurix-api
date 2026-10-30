<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class ListWorkspaces
{
    /**
     * Every workspace the user belongs to, with their role and its member count in each, oldest
     * first. The count tells deleting an account which workspaces take teammates down with it.
     *
     * @return Collection<int, Workspace>
     */
    public function handle(User $user): Collection
    {
        return $user->workspaces()->withCount('members')->oldest('workspaces.created_at')->get();
    }
}
