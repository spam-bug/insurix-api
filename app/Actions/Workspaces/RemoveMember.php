<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\WorkspaceRole;
use App\Models\WorkspaceMember;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveMember
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Takes a helper out of the workspace. The owner can't be removed; ownership moves first.
     */
    public function handle(WorkspaceMember $member): void
    {
        if ($member->role === WorkspaceRole::Owner) {
            throw ValidationException::withMessages(['member' => 'The owner can’t be removed from their workspace.']);
        }

        DB::transaction(fn () => $member->delete());

        $this->announceWorkspaceChange->handle($member->workspace_id, new WorkspaceChangeData(ChangeTopic::Team, ChangeAction::Deleted, $member->id));
    }
}
