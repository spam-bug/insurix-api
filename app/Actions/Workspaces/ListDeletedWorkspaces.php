<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Collection;

class ListDeletedWorkspaces
{
    /**
     * The workspaces the user owns that are deleted but can still be restored, newest first.
     *
     * @return Collection<int, Workspace>
     */
    public function handle(User $user): Collection
    {
        return Workspace::onlyTrashed()
            ->whereHas('members', fn ($query) => $query->where('user_id', $user->id)->where('role', WorkspaceRole::Owner))
            ->latest('deleted_at')
            ->get(['id', 'name', 'deleted_at']);
    }
}
