<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Models\Policy;
use Carbon\CarbonImmutable;

class EstimatePaymentCommission
{
    /**
     * What a premium should earn the agent: the amount times their first-year rate while the
     * policy is in its first year, and their renewal rate after. An estimate only; insurers
     * work out the real figure their own way.
     */
    public function handle(Policy $policy, CarbonImmutable $dueOn, float $amount): float
    {
        $inFirstYear = $policy->effective_date === null || $dueOn->lessThan($policy->effective_date->addYear());
        $rate = (float) ($inFirstYear ? $policy->commission_rate : $policy->renewal_commission_rate);

        return round($amount * $rate / 100, 2);
    }
}
