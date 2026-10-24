<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\DataTransferObject\Insights\InsightReportFiltersData;
use App\Models\Lead;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
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
     * Every report on the Insights page, in one response. Pipeline speed and the commission chart
     * follow the chosen period. The rest describes the workspace as it is now.
     *
     * @return array<string, mixed>
     */
    public function handle(Workspace $workspace, InsightReportFiltersData $filters): array
    {
        $key = "insights:{$workspace->id}:reports:{$filters->period->value}:{$filters->offset}";

        return Cache::tags("workspace:{$workspace->id}")->remember($key, now()->addMinutes(5), function () use ($workspace, $filters) {
            $today = CarbonImmutable::now($workspace->timezone);
            [$start, $end] = $filters->period->range($today, $filters->offset);
            [$previousStart] = $filters->period->range($today, $filters->offset + 1);
            $stalled = $this->listStalledLeads->handle($workspace);

            return [
                'period' => [
                    'value' => $filters->period->value,
                    'offset' => $filters->offset,
                    'label' => $filters->period->label($start),
                    'previous_label' => $filters->period->label($previousStart),
                    'starts_on' => $start->toDateString(),
                    'ends_on' => $end->toDateString(),
                ],
                'lead_sources' => $this->measureLeadSources->handle($workspace),
                'pipeline_speed' => $this->measurePipelineSpeed->handle($workspace, $start, $end),
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
                'commission_by_month' => $this->chartCommissionByMonth->handle($workspace, $end),
            ];
        });
    }
}
