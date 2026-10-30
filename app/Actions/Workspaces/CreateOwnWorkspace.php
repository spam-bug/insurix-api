<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\DataTransferObject\Workspaces\WorkspaceData;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;

class CreateOwnWorkspace
{
    public function __construct(private CreateWorkspace $createWorkspace) {}

    /** The insurance workspace a user gets without going through onboarding, named after them. */
    public function handle(User $user): Workspace
    {
        return $this->createWorkspace->handle($user, new WorkspaceData(
            name: "{$user->name}'s workspace",
            type: WorkspaceType::Insurance,
            timezone: Workspace::DEFAULT_TIMEZONE,
        ));
    }
}
