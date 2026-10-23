<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Enums\WorkspaceRole;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveMember
{
    /**
     * Takes a helper out of the workspace. The owner can't be removed; ownership moves first.
     */
    public function handle(WorkspaceMember $member): void
    {
        if ($member->role === WorkspaceRole::Owner) {
            throw ValidationException::withMessages(['member' => 'The owner can’t be removed from their workspace.']);
        }

        DB::transaction(fn () => $member->delete());

        Cache::tags("workspace:{$member->workspace_id}")->flush();
    }
}
