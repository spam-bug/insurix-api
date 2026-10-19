<?php

namespace App\Actions\Insights;

use App\Actions\FollowUps\SummarizeFollowUps;
use App\Actions\Leads\ScoreLead;
use App\Actions\Policies\ListPoliciesNeedingAttention;
use App\Enums\LeadSource;
use App\Enums\PolicyStatus;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * "What should I do next to grow my business?": a few concrete suggestions from the agent's
 * own numbers, most pressing first. Each has a code the app turns into a link, so the agent
 * can act on it in one click.
 */
class SuggestNextActions
{
    /** At most this many suggestions, so the list stays something to act on. */
    public const LIMIT = 5;

    /** A hot lead with no contact for this many days gets a nudge. */
    public const HOT_QUIET_DAYS = 7;

    /** Renewals this many days out with no open follow-up. Automatic reminders start 30 days out. */
    public const RENEWAL_WINDOW_DAYS = 60;

    /** No new leads for this many days suggests finding more. */
    public const NO_NEW_LEADS_DAYS = 14;

    /** A source converting at least this well is worth more of the agent's time. */
    public const GOOD_SOURCE_PERCENT = 20;

    public function __construct(
        private SummarizeFollowUps $summarizeFollowUps,
        private ListStalledLeads $listStalledLeads,
        private MeasureLeadSources $measureLeadSources,
    ) {}

    /**
     * @return array<int, array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}>
     */
    public function handle(User $agent): array
    {
        $today = CarbonImmutable::now($agent->timezone)->startOfDay();

        $quiet = $this->listStalledLeads->handle($agent, self::HOT_QUIET_DAYS);
        $hot = $quiet->filter(fn (Lead $lead) => $lead->score !== null && $lead->score >= ScoreLead::HOT_SCORE)->values();
        $stalled = $quiet->filter(fn (Lead $lead) => $lead->days_quiet >= ListStalledLeads::QUIET_DAYS)->diff($hot)->values();

        $suggestions = array_filter([
            $this->overdueFollowUps($agent),
            $this->hotLeadsWaiting($hot),
            $this->renewalsWithoutFollowUp($agent, $today),
            $this->stuckApplications($agent, $today),
            $this->bestSource($agent),
            $this->stalledLeads($stalled),
            $this->newLeads($agent),
        ]);

        return array_slice(array_values($suggestions), 0, self::LIMIT);
    }

    /**
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}|null
     */
    private function overdueFollowUps(User $agent): ?array
    {
        $overdue = $this->summarizeFollowUps->handle($agent)['overdue'];
        if ($overdue === 0) {
            return null;
        }

        return $this->suggestion(
            'overdue_follow_ups',
            $overdue === 1 ? '1 follow-up is overdue' : "{$overdue} follow-ups are overdue",
            'Clear them first, or move them to a day that works.',
            $overdue,
        );
    }

    /**
     * @param  Collection<int, Lead>  $hot
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}|null
     */
    private function hotLeadsWaiting(Collection $hot): ?array
    {
        if ($hot->isEmpty()) {
            return null;
        }

        if ($hot->count() === 1) {
            $lead = $hot->first();

            return $this->suggestion(
                'hot_leads_waiting',
                "{$lead->name} is hot but hasn't heard from you in {$lead->days_quiet} days",
                "Score {$lead->score}. A quick call keeps them warm.",
                1,
                leadId: $lead->id,
            );
        }

        return $this->suggestion(
            'hot_leads_waiting',
            "{$hot->count()} hot leads haven't heard from you in ".self::HOT_QUIET_DAYS.'+ days',
            $this->names($hot->pluck('name')).". They're the closest to buying.",
            $hot->count(),
        );
    }

    /**
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}|null
     */
    private function renewalsWithoutFollowUp(User $agent, CarbonImmutable $today): ?array
    {
        $policies = $agent->policies()
            ->with('client:id,name')
            ->where('status', PolicyStatus::InForce)
            ->whereBetween('renewal_date', [$today->toDateString(), $today->addDays(self::RENEWAL_WINDOW_DAYS)->toDateString()])
            ->whereDoesntHave('followUps', fn (Builder $query) => $query->whereNull('completed_at'))
            ->orderBy('renewal_date')
            ->get();

        if ($policies->isEmpty()) {
            return null;
        }

        $count = $policies->count();

        return $this->suggestion(
            'renewals_without_follow_up',
            $count === 1
                ? '1 renewal in the next '.self::RENEWAL_WINDOW_DAYS.' days has no follow-up yet'
                : "{$count} renewals in the next ".self::RENEWAL_WINDOW_DAYS.' days have no follow-up yet',
            $this->names($policies->map(fn (Policy $policy) => "{$policy->client->name} ({$policy->renewal_date->format('M j')})")).'. Reach out early to keep them.',
            $count,
        );
    }

    /**
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}|null
     */
    private function stuckApplications(User $agent, CarbonImmutable $today): ?array
    {
        $days = ListPoliciesNeedingAttention::PENDING_DAYS;
        $count = $agent->policies()
            ->where('status', PolicyStatus::Pending)
            ->where('created_at', '<', $today->subDays($days)->utc())
            ->count();

        if ($count === 0) {
            return null;
        }

        return $this->suggestion(
            'stuck_applications',
            $count === 1 ? "1 application has been pending {$days}+ days" : "{$count} applications have been pending {$days}+ days",
            'Check with the insurer for missing requirements.',
            $count,
        );
    }

    /**
     * The source that converts best, when it converts well enough to be worth more time.
     *
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}|null
     */
    private function bestSource(User $agent): ?array
    {
        $best = collect($this->measureLeadSources->handle($agent))
            ->first(fn (array $source) => $source['enough_data'] && $source['rate_percent'] >= self::GOOD_SOURCE_PERCENT);

        if ($best === null) {
            return null;
        }

        $rate = $best['rate_percent'];

        return match (LeadSource::from($best['source'])) {
            LeadSource::Referral => $this->suggestion(
                'ask_for_referrals',
                "Referrals convert at {$rate}% for you",
                'Ask your newest clients ('.$this->names($agent->clients()->latest()->latest('id')->limit(3)->pluck('name')).') who else they know.',
                $best['leads'],
                source: $best['source'],
            ),
            LeadSource::ShareableForm => $this->suggestion(
                'share_form',
                "Your shareable form converts at {$rate}%",
                'Post your link again, or send it to people who asked about coverage.',
                $best['leads'],
                source: $best['source'],
            ),
            default => $this->suggestion(
                'best_source',
                "{$best['label']} leads convert at {$rate}% for you",
                'Your best channel so far. Put more of your week into it.',
                $best['leads'],
                source: $best['source'],
            ),
        };
    }

    /**
     * @param  Collection<int, Lead>  $stalled
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}|null
     */
    private function stalledLeads(Collection $stalled): ?array
    {
        if ($stalled->isEmpty()) {
            return null;
        }

        $count = $stalled->count();

        return $this->suggestion(
            'stalled_leads',
            $count === 1 ? '1 lead has gone quiet for 2+ weeks' : "{$count} leads have gone quiet for 2+ weeks",
            'Follow up, or mark them lost to keep your pipeline honest.',
            $count,
            leadId: $count === 1 ? $stalled->first()->id : null,
        );
    }

    /**
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}|null
     */
    private function newLeads(User $agent): ?array
    {
        $latest = $agent->leads()->max('created_at');

        if ($latest === null) {
            return $this->suggestion('no_new_leads', 'Add your first leads', "Share your form link, or add someone you've talked to.", 0);
        }

        $days = (int) CarbonImmutable::parse($latest)->diffInDays(CarbonImmutable::now());
        if ($days < self::NO_NEW_LEADS_DAYS) {
            return null;
        }

        return $this->suggestion(
            'no_new_leads',
            "No new leads in {$days} days",
            'Share your form link on social media, or ask a happy client for a referral.',
            $days,
        );
    }

    /**
     * "Ana, Ben and Carla", or "Ana, Ben, Carla and 2 more".
     *
     * @param  Collection<int, string>  $names
     */
    private function names(Collection $names, int $shown = 3): string
    {
        $listed = $names->take($shown);
        $more = $names->count() - $listed->count();

        if ($more > 0) {
            return $listed->implode(', ')." and {$more} more";
        }

        return $listed->count() > 1 ? $listed->slice(0, -1)->implode(', ').' and '.$listed->last() : (string) $listed->first();
    }

    /**
     * @return array{code: string, title: string, detail: string, count: int, lead_id: int|null, source: string|null}
     */
    private function suggestion(string $code, string $title, string $detail, int $count, ?int $leadId = null, ?string $source = null): array
    {
        return ['code' => $code, 'title' => $title, 'detail' => $detail, 'count' => $count, 'lead_id' => $leadId, 'source' => $source];
    }
}
