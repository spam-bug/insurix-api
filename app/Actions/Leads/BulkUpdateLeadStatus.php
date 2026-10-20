<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\BulkLeadStatusData;
use App\Enums\LeadStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkUpdateLeadStatus
{
    public function __construct(private UpdateLeadStatus $updateLeadStatus) {}

    /**
     * Changes the status of the agent's own leads among the selected ones, logging each change.
     * Leads already promoted to clients are left as they are.
     *
     * @return int how many leads were changed
     */
    public function handle(User $agent, BulkLeadStatusData $data): int
    {
        return DB::transaction(function () use ($agent, $data) {
            $leads = $agent->leads()->whereKey($data->ids)->whereNotIn('status', [$data->status, LeadStatus::Converted])->get();

            foreach ($leads as $lead) {
                $this->updateLeadStatus->handle($lead, $data->status, $agent);
            }

            return $leads->count();
        });
    }
}
