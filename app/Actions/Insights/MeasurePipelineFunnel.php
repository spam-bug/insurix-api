<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Workspace;
use Carbon\CarbonImmutable;

class MeasurePipelineFunnel
{
    /** Pipeline stages in order. A lead that reached a stage counts for every stage before it. */
    private const STAGES = [
        LeadStatus::Prospect,
        LeadStatus::Contacted,
        LeadStatus::MeetingScheduled,
        LeadStatus::Qualified,
        LeadStatus::Converted,
    ];

    /**
     * "Where do my leads drop off?": of the leads added in the period, how many reached each
     * stage, with the share that moved on from the stage before. A lead's furthest stage comes
     * from its status and its status history, so lost leads still count for how far they got.
     *
     * @return array{stages: array<int, array{status: string, label: string, reached: int, conversion_percent: int|null}>, lost: int}
     */
    public function handle(Workspace $workspace, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $leads = $workspace->leads()->whereBetween('created_at', [$start->utc(), $end->utc()])->get(['id', 'status']);

        $history = LeadActivity::query()
            ->whereIn('lead_id', $leads->modelKeys())
            ->where('type', LeadActivityType::StatusChanged)
            ->get(['lead_id', 'properties'])
            ->groupBy('lead_id');

        $furthest = $leads->map(fn (Lead $lead) => collect([$lead->status->value])
            ->merge($history->get($lead->id, collect())->flatMap(fn (LeadActivity $activity) => [$activity->properties['from'] ?? null, $activity->properties['to'] ?? null]))
            ->map(fn (?string $status) => $this->rank($status))
            ->max());

        $previousReached = null;

        $stages = collect(self::STAGES)->map(function (LeadStatus $status, int $rank) use ($furthest, &$previousReached) {
            $reached = $furthest->filter(fn (int $furthestRank) => $furthestRank >= $rank)->count();
            $conversion = $previousReached ? (int) round($reached / $previousReached * 100) : null;
            $previousReached = $reached;

            return ['status' => $status->value, 'label' => $status->label(), 'reached' => $reached, 'conversion_percent' => $conversion];
        });

        return [
            'stages' => $stages->all(),
            'lost' => $leads->filter(fn (Lead $lead) => $lead->status === LeadStatus::Lost)->count(),
        ];
    }

    /** A status's place in the pipeline. Lost has none, so it counts as the first stage. */
    private function rank(?string $status): int
    {
        $rank = array_search(LeadStatus::tryFrom((string) $status), self::STAGES, true);

        return $rank === false ? 0 : $rank;
    }
}
