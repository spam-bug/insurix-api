<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Leads\LeadSelectionData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class BulkDeleteLeads
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Deletes the workspace's leads among the selected ones.
     *
     * @return int how many leads were deleted
     */
    public function handle(Workspace $workspace, LeadSelectionData $data): int
    {
        $deleted = DB::transaction(fn () => $workspace->leads()->whereKey($data->ids)->delete());

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Deleted, null, null, $deleted));

        return $deleted;
    }
}
