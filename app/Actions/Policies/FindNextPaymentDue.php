<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use Carbon\CarbonImmutable;

class FindNextPaymentDue
{
    /**
     * The next premium due on or after $today, counting from the effective date. Null for
     * single-payment policies, ones without an effective date, and ones no longer in force.
     * Steps from the effective date itself each time, so month-end dates don't drift.
     */
    public function handle(Policy $policy, ?CarbonImmutable $today = null): ?CarbonImmutable
    {
        $months = $policy->payment_frequency->monthsBetweenPayments();

        if ($months === null || $policy->effective_date === null || $policy->status !== PolicyStatus::InForce) {
            return null;
        }

        $today ??= CarbonImmutable::today();

        for ($period = 0; ($date = $policy->effective_date->addMonthsNoOverflow($period * $months))->lessThan($today); $period++);

        return $date;
    }
}
