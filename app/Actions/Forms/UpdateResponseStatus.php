<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Forms\ResponseStatusData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\FormResponseStatus;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class UpdateResponseStatus
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** Dismisses responses nobody needs to act on, or brings them back. Added ones are left alone. */
    public function handle(Workspace $workspace, ResponseStatusData $data): int
    {
        $updated = DB::transaction(fn () => $workspace->formResponses()
            ->whereIn('id', $data->responseIds)
            ->where('status', '!=', FormResponseStatus::Added)
            ->update(['status' => $data->status]));

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Updated, count: $updated));

        return $updated;
    }
}
