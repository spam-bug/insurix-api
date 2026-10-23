<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class CountPoliciesByStatus
{
    /**
     * Counts every status for the agent, including those with no policies, plus a total.
     *
     * @return array<string, int>
     */
    public function handle(User $agent): array
    {
        return Cache::tags("agent:{$agent->id}")->rememberForever("policies:{$agent->id}:status-counts", function () use ($agent) {
            $counts = $agent->policies()
                ->toBase()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $byStatus = collect(PolicyStatus::cases())
                ->mapWithKeys(fn (PolicyStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
                ->all();

            return ['total' => array_sum($byStatus), ...$byStatus];
        });
    }
}
