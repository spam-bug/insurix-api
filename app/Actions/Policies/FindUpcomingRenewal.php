<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use Carbon\CarbonImmutable;

class FindUpcomingRenewal
{
    public function __construct(private FindNextRenewalDate $findNextRenewalDate) {}

    /**
     * The next renewal, when it falls between $today and $days from now. Monthly and quarterly
     * terms use their own shorter window instead, so they don't count as coming up all the time.
     */
    public function handle(Policy $policy, CarbonImmutable $today, int $days): ?CarbonImmutable
    {
        $renewal = $this->findNextRenewalDate->handle($policy, $today);

        if ($renewal === null || $renewal->lessThan($today)) {
            return null;
        }

        $months = $policy->renewal_term->months();
        $window = $months !== null && $months < 12 ? min($days, $policy->renewal_term->attentionDays()) : $days;

        return $renewal->lessThanOrEqualTo($today->addDays($window)) ? $renewal : null;
    }
}
