<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Policies\BulkPolicyStatusData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\PolicyStatus;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class BulkUpdatePolicyStatus
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Moves the selected policies between in force, lapsed and cancelled. Pending and declined
     * policies are skipped, since applications are approved or declined through their own steps.
     *
     * @return int how many policies were changed
     */
    public function handle(Workspace $workspace, BulkPolicyStatusData $data): int
    {
        $updated = DB::transaction(function () use ($workspace, $data) {
            $policies = $workspace->policies()
                ->whereKey($data->ids)
                ->whereIn('status', PolicyStatus::bulkSettable())
                ->where('status', '!=', $data->status)
                ->get();

            $policies->each->update(['status' => $data->status]);

            return $policies->count();
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, null, null, $updated));

        return $updated;
    }
}
