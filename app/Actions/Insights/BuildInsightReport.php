<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Lead;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class BuildInsightReport
{
    /** Stalled leads listed by name; the rest are only counted. */
    private const STALLED_LISTED = 10;

    public function __construct(
        private MeasureLeadSources $measureLeadSources,
        private MeasurePipelineSpeed $measurePipelineSpeed,
        private ListStalledLeads $listStalledLeads,
        private MeasureRetention $measureRetention,
        private RankProducts $rankProducts,
        private ChartCommissionByMonth $chartCommissionByMonth,
    ) {}

    /**
     * Every report on the Insights page, in one response.
     *
     * @return array<string, mixed>
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("insights:{$workspace->id}:reports", now()->addMinutes(5), function () use ($workspace) {
            $stalled = $this->listStalledLeads->handle($workspace);

            return [
                'lead_sources' => $this->measureLeadSources->handle($workspace),
                'pipeline_speed' => $this->measurePipelineSpeed->handle($workspace),
                'stalled_leads' => [
                    'quiet_days' => ListStalledLeads::QUIET_DAYS,
                    'total' => $stalled->count(),
                    'leads' => $stalled->take(self::STALLED_LISTED)->map(fn (Lead $lead) => [
                        'id' => $lead->id,
                        'name' => $lead->name,
                        'status' => ['value' => $lead->status->value, 'label' => $lead->status->label()],
                        'score' => $lead->score,
                        'days_quiet' => $lead->days_quiet,
                    ])->values()->all(),
                ],
                'retention' => $this->measureRetention->handle($workspace),
                'top_products' => $this->rankProducts->handle($workspace),
                'commission_by_month' => $this->chartCommissionByMonth->handle($workspace),
            ];
        });
    }
}
