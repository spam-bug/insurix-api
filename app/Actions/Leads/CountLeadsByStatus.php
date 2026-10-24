<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\LeadStatus;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class CountLeadsByStatus
{
    /**
     * Counts every status in the workspace, including those with no leads, plus a total.
     *
     * @return array<string, int>
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("leads:{$workspace->id}:status-counts", function () use ($workspace) {
            $counts = $workspace->leads()
                ->toBase()
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status');

            $byStatus = collect(LeadStatus::cases())
                ->mapWithKeys(fn (LeadStatus $status) => [$status->value => (int) ($counts[$status->value] ?? 0)])
                ->all();

            return ['total' => array_sum($byStatus), ...$byStatus];
        });
    }
}
