<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\RenewalTerm;
use App\Models\Policy;
use Carbon\CarbonImmutable;

class FindNextRenewalDate
{
    /**
     * The next renewal on or after $today. Set terms count from the effective date (the first
     * renewal is one term after it); a custom term is its fixed date, even once it has passed.
     * Steps from the effective date itself each time, so month-end dates don't drift.
     */
    public function handle(Policy $policy, ?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $months = $policy->renewal_term?->months();

        if ($months === null || $policy->effective_date === null) {
            return $policy->renewal_term === RenewalTerm::Custom ? $policy->renewal_date : null;
        }

        $today ??= CarbonImmutable::today();

        for ($period = 1; ($date = $policy->effective_date->addMonthsNoOverflow($period * $months))->lessThan($today); $period++);

        return $date;
    }
}
