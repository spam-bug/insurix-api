<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\OwnershipData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\WorkspaceRole;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class TransferOwnership
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Makes a member the owner; the current owner stays on as a member. A workspace has one
     * owner, so both roles change together.
     */
    public function handle(Workspace $workspace, OwnershipData $data): void
    {
        DB::transaction(function () use ($workspace, $data) {
            $workspace->members()->where('role', WorkspaceRole::Owner)->update(['role' => WorkspaceRole::Member]);
            $workspace->members()->whereKey($data->memberId)->update(['role' => WorkspaceRole::Owner]);
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Team, ChangeAction::Updated, $data->memberId));
    }
}
