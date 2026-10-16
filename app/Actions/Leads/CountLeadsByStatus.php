<?php

namespace App\Actions\Leads;

use App\Enums\LeadStatus;
use App\Models\User;

class CountLeadsByStatus
{
    /**
     * Counts every status for the agent, including those with no leads, plus a total.
     *
     * @return array<string, int>
     */
    public function handle(User $agent): array
    {
        $counts = $agent->leads()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $byStatus = collect(LeadStatus::cases())
            ->mapWithKeys(fn (LeadStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();

        return ['total' => array_sum($byStatus), ...$byStatus];
    }
}
