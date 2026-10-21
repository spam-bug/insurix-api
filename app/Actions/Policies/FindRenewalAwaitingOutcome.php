<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Enums\RenewalTerm;
use App\Models\Policy;
use App\Models\PolicyRenewal;
use Carbon\CarbonImmutable;

class FindRenewalAwaitingOutcome
{
    /** Renewal dates this far back still wait for an outcome; older ones are let go. */
    public const LOOKBACK_DAYS = 90;

    /**
     * The earliest renewal date that's due for an outcome and doesn't have one yet: from
     * LOOKBACK_DAYS ago up to the term's attention window ahead. Needs the renewals relation;
     * loads it when missing.
     */
    public function handle(Policy $policy, ?CarbonImmutable $today = null): ?CarbonImmutable
    {
        if ($policy->status !== PolicyStatus::InForce || ! $policy->renewal_term?->tracksOutcomes()) {
            return null;
        }

        $today ??= CarbonImmutable::today();
        $recorded = $policy->renewals->map(fn (PolicyRenewal $renewal) => $renewal->due_on->toDateString())->all();

        foreach ($this->renewalDatesBetween($policy, $today->subDays(self::LOOKBACK_DAYS), $today->addDays($policy->renewal_term->attentionDays())) as $date) {
            if (! in_array($date->toDateString(), $recorded, true)) {
                return $date;
            }
        }

        return null;
    }

    /**
     * @return array<int, CarbonImmutable>
     */
    private function renewalDatesBetween(Policy $policy, CarbonImmutable $from, CarbonImmutable $to): array
    {
        $months = $policy->renewal_term->months();

        if ($months === null || $policy->effective_date === null) {
            $date = $policy->renewal_term === RenewalTerm::Custom ? $policy->renewal_date : null;

            return $date && $date->betweenIncluded($from, $to) ? [$date] : [];
        }

        $dates = [];
        for ($period = 1; ($date = $policy->effective_date->addMonthsNoOverflow($period * $months))->lessThanOrEqualTo($to); $period++) {
            if ($date->greaterThanOrEqualTo($from)) {
                $dates[] = $date;
            }
        }

        return $dates;
    }
}
