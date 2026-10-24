<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class CountPoliciesByStatus
{
    /**
     * Counts every status in the workspace, including those with no policies, plus a total.
     *
     * @return array<string, int>
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("policies:{$workspace->id}:status-counts", function () use ($workspace) {
            $counts = $workspace->policies()
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
