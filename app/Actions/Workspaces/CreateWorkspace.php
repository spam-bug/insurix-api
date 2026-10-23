<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\DataTransferObject\Workspaces\WorkspaceData;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    /**
     * Creates a workspace of the given type with the user as its owner.
     */
    public function handle(User $owner, WorkspaceData $data): Workspace
    {
        return DB::transaction(function () use ($owner, $data) {
            $workspace = Workspace::create($data->toArray());
            $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner]);

            return $workspace;
        });
    }
}
