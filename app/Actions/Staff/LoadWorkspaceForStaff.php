<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Enums\WorkspaceRole;
use App\Models\Workspace;

class LoadWorkspaceForStaff
{
    /** Everything the workspace's console page shows: owner, members, counts, Premium history, audit log. */
    public function handle(Workspace $workspace): Workspace
    {
        return $workspace->load([
            'users' => fn ($query) => $query->select(['users.id', 'users.name', 'users.email'])->wherePivot('role', WorkspaceRole::Owner),
            'members.user:id,name,email',
            'premiumGrants' => fn ($query) => $query->latest(),
            'staffActivities' => fn ($query) => $query->with('staff:id,name')->latest('id')->limit(50),
        ])->loadCount(['members', 'leads', 'clients']);
    }
}
