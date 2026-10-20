<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\LeadStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkUpdateLeadStatus
{
    public function __construct(private UpdateLeadStatus $updateLeadStatus) {}

    /**
     * Changes the status of the agent's own leads among $leadIds, logging each change.
     * Leads already promoted to clients are left as they are.
     *
     * @param  array<int, string>  $leadIds
     * @return int how many leads were changed
     */
    public function handle(User $agent, array $leadIds, LeadStatus $status): int
    {
        return DB::transaction(function () use ($agent, $leadIds, $status) {
            $leads = $agent->leads()->whereKey($leadIds)->whereNotIn('status', [$status, LeadStatus::Converted])->get();

            foreach ($leads as $lead) {
                $this->updateLeadStatus->handle($lead, $status, $agent);
            }

            return $leads->count();
        });
    }
}
