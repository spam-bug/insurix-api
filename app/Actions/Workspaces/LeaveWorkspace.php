<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveWorkspace
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Takes the user out of a workspace they help in. The owner hands it over first, so a
     * workspace is never left without one.
     */
    public function handle(Workspace $workspace, User $user): void
    {
        $membership = $workspace->members()->where('user_id', $user->id)->sole();

        if ($membership->role === WorkspaceRole::Owner) {
            throw ValidationException::withMessages(['workspace' => 'Hand the workspace to another member before you leave it.']);
        }

        DB::transaction(fn () => $membership->delete());

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Team, ChangeAction::Deleted, $membership->id));
    }
}
