<?php

namespace App\Actions\Insights;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ListStalledLeads
{
    /** An open lead with no contact for this many days has stalled. */
    public const QUIET_DAYS = 14;

    /**
     * Open leads the agent hasn't been in touch with for $quietDays or more, highest score
     * first. Each lead gets days_quiet: days since the last touch, or since it was added.
     *
     * @return Collection<int, Lead>
     */
    public function handle(User $agent, int $quietDays = self::QUIET_DAYS): Collection
    {
        $now = CarbonImmutable::now();

        return $agent->leads()
            ->whereIn('status', LeadStatus::open())
            ->withMax(['activities as last_touched_at' => fn (Builder $query) => $query
                ->whereNotNull('lead_activities.user_id')
                ->whereIn('type', LeadActivityType::touches()),
            ], 'created_at')
            ->get()
            ->each(function (Lead $lead) use ($now) {
                $lead->days_quiet = (int) CarbonImmutable::parse($lead->last_touched_at ?? $lead->created_at)->diffInDays($now);
            })
            ->filter(fn (Lead $lead) => $lead->days_quiet >= $quietDays)
            ->sortBy([['score', 'desc'], ['days_quiet', 'desc']])
            ->values();
    }
}
