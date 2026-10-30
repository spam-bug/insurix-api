<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\DataTransferObject\Workspaces\WorkspaceSettingsData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class UpdateWorkspace
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * The workspace's name and the timezone its day starts in.
     */
    public function handle(Workspace $workspace, WorkspaceSettingsData $data): Workspace
    {
        DB::transaction(fn () => $workspace->update($data->toArray()));

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Workspace, ChangeAction::Updated, $workspace->id, $workspace->name));

        return $workspace;
    }
}
