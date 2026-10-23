<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\BulkLeadStatusData;
use App\Enums\LeadStatus;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BulkUpdateLeadStatus
{
    public function __construct(private UpdateLeadStatus $updateLeadStatus) {}

    /**
     * Changes the status of the workspace's leads among the selected ones, logging each change.
     * Leads already promoted to clients are left as they are.
     *
     * @return int how many leads were changed
     */
    public function handle(Workspace $workspace, BulkLeadStatusData $data, User $actor): int
    {
        $updated = DB::transaction(function () use ($workspace, $data, $actor) {
            $leads = $workspace->leads()->whereKey($data->ids)->whereNotIn('status', [$data->status, LeadStatus::Converted])->get();

            foreach ($leads as $lead) {
                $this->updateLeadStatus->handle($lead, $data->status, $actor);
            }

            return $leads->count();
        });

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $updated;
    }
}
