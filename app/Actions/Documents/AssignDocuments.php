<?php

declare(strict_types=1);

namespace App\Actions\Documents;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Documents\AssignDocumentsData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class AssignDocuments
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Gives the selected documents to one client. Documents moving from another client lose
     * their policy link, since that policy isn't the new client's.
     *
     * @return int how many documents were assigned
     */
    public function handle(Workspace $workspace, AssignDocumentsData $data): int
    {
        $assigned = DB::transaction(fn () => $workspace->documents()
            ->whereKey($data->ids)
            ->where(fn ($query) => $query->whereNull('client_id')->orWhere('client_id', '!=', $data->clientId))
            ->update(['client_id' => $data->clientId, 'policy_id' => null]));

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Documents, ChangeAction::Updated, null, null, $assigned));

        return $assigned;
    }
}
