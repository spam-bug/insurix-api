<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use Carbon\CarbonImmutable;

class FindNextPaymentDue
{
    /** How long after a missed premium an insurer usually keeps the policy alive. */
    public const GRACE_DAYS = 31;

    /**
     * The earliest premium nobody has recorded as paid, counting from the effective date. It can
     * be in the past: that premium is overdue. Premiums due before the policy was added to the
     * workspace aren't tracked, so an imported book doesn't start out years behind. Null for
     * policies without an effective date, ones no longer in force, and a paid single premium.
     *
     * Payments are recorded in order, so the last paid due date is enough. Lists load it as
     * `last_paid_due_on` (withMax); without it, it's read here.
     */
    public function handle(Policy $policy): ?CarbonImmutable
    {
        if ($policy->effective_date === null || $policy->status !== PolicyStatus::InForce) {
            return null;
        }

        $lastPaid = array_key_exists('last_paid_due_on', $policy->getAttributes())
            ? $policy->getAttribute('last_paid_due_on')
            : $policy->payments()->max('due_on');
        $trackedFrom = CarbonImmutable::parse($policy->created_at?->toDateString() ?? $policy->effective_date);
        $paidThrough = $lastPaid === null ? null : CarbonImmutable::parse($lastPaid)->startOfDay();
        $months = $policy->payment_frequency->monthsBetweenPayments();
        $isOpen = fn (CarbonImmutable $date) => $date->greaterThanOrEqualTo($trackedFrom) && ($paidThrough === null || $date->greaterThan($paidThrough));

        if ($months === null) {
            return $isOpen($policy->effective_date) ? $policy->effective_date : null;
        }

        // Steps from the effective date itself each time, so month-end dates don't drift.
        for ($period = 0; ! $isOpen($date = $policy->effective_date->addMonthsNoOverflow($period * $months)); $period++);

        return $date;
    }
}
