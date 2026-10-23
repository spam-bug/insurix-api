<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use Carbon\CarbonImmutable;
/**
 * "Which prospects are most likely to become clients?": a 0 to 100 score from simple rules,
 * each with a reason the agent can read. Clients and lost leads aren't scored.
 */
use Illuminate\Support\Facades\DB;

class ScoreLead
{
    /** A score from here up is "hot". */
    public const HOT_SCORE = 70;

    /** A score from here up (and below hot) is "warm". */
    public const WARM_SCORE = 40;

    /** @var array<string, int> */
    private const STAGE_POINTS = [
        'prospect' => 10,
        'contacted' => 25,
        'meeting_scheduled' => 45,
        'qualified' => 60,
    ];

    /** A source needs this many leads before its conversion rate counts. */
    private const MIN_LEADS_FOR_SOURCE_RATE = 3;

    /**
     * Scores the lead and saves it. Pass $sourceRates (source => conversion rate, 0 to 1)
     * when scoring many leads, so they aren't worked out again for each one.
     *
     * @param  array<string, float>|null  $sourceRates
     */
    public function handle(Lead $lead, ?array $sourceRates = null, ?CarbonImmutable $now = null): Lead
    {
        [$score, $reasons] = $this->assess($lead, $sourceRates ?? $this->sourceRates($lead), $now ?? CarbonImmutable::now());

        DB::transaction(fn () => $lead->forceFill(['score' => $score, 'score_reasons' => $reasons])->saveQuietly());

        return $lead;
    }

    /**
     * Converted leads over all leads, per source, in the workspace that owns $lead.
     *
     * @return array<string, float>
     */
    public function sourceRates(Lead $lead): array
    {
        return Lead::where('workspace_id', $lead->workspace_id)
            ->toBase()
            ->selectRaw('source, count(*) as total, sum(case when status = ? then 1 else 0 end) as converted', [LeadStatus::Converted->value])
            ->groupBy('source')
            ->having('total', '>=', self::MIN_LEADS_FOR_SOURCE_RATE)
            ->get()
            ->mapWithKeys(fn (object $row) => [$row->source => (int) $row->converted / (int) $row->total])
            ->all();
    }

    /**
     * @param  array<string, float>  $sourceRates
     * @return array{0: int|null, 1: array<int, array{label: string, points: int}>|null}
     */
    private function assess(Lead $lead, array $sourceRates, CarbonImmutable $now): array
    {
        if (! isset(self::STAGE_POINTS[$lead->status->value])) {
            return [null, null];
        }

        $reasons = [['label' => $lead->status->label(), 'points' => self::STAGE_POINTS[$lead->status->value]]];

        $lastTouch = $this->lastTouch($lead);
        $days = (int) $lastTouch->diffInDays($now);
        $reasons[] = match (true) {
            $days <= 3 => ['label' => 'In touch in the last 3 days', 'points' => 15],
            $days <= 7 => ['label' => 'In touch this week', 'points' => 10],
            $days <= 14 => ['label' => 'In touch in the last 2 weeks', 'points' => 5],
            $days <= 30 => ['label' => "No contact in {$days} days", 'points' => -10],
            default => ['label' => "No contact in {$days} days", 'points' => -20],
        };

        if ($lead->meeting_at?->isFuture()) {
            $reasons[] = ['label' => 'Meeting coming up', 'points' => 10];
        }

        if ($lead->interest) {
            $reasons[] = ['label' => "Interested in {$lead->interest->label()}", 'points' => 5];
        }

        if ($lead->email && $lead->phone) {
            $reasons[] = ['label' => 'Has email and phone', 'points' => 5];
        }

        $rate = $sourceRates[$lead->source->value] ?? null;
        if ($rate !== null && $rate >= 0.15) {
            $percent = (int) round($rate * 100);
            $reasons[] = ['label' => "{$lead->source->label()} leads convert well for you ({$percent}%)", 'points' => $rate >= 0.3 ? 10 : 5];
        }

        if ($lead->followUps()->whereNull('completed_at')->where('due_at', '<', $now)->exists()) {
            $reasons[] = ['label' => 'Overdue follow-up', 'points' => -10];
        }

        $score = max(0, min(100, array_sum(array_column($reasons, 'points'))));

        return [$score, $reasons];
    }

    /**
     * When the agent was last in touch with the lead (see LeadActivityType::touches()). A lead
     * nobody has touched yet counts from when it was added.
     */
    private function lastTouch(Lead $lead): CarbonImmutable
    {
        $touched = $lead->activities()
            ->whereNotNull('user_id')
            ->whereIn('type', LeadActivityType::touches())
            ->max('created_at');

        return CarbonImmutable::parse($touched ?? $lead->created_at);
    }
}
