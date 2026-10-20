<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\User;
use Carbon\CarbonImmutable;

class SummarizeCommissions
{
    /**
     * "How much commission am I earning or expecting?", from first-year commission on each
     * policy (annual premium times the agent's rate):
     *  - received_this_year: marked received this calendar year
     *  - receivable: in force, not marked received yet
     *  - expected: on pending applications, if they're approved
     *
     * @return array{received_this_year: float, receivable: float, expected: float, annual_premium_in_force: float, policies_in_force: int, pending_applications: int}
     */
    public function handle(User $agent): array
    {
        $startOfYear = CarbonImmutable::now($agent->timezone)->startOfYear();
        $policies = $agent->policies()->get();

        $inForce = $policies->filter(fn (Policy $policy) => $policy->status === PolicyStatus::InForce);
        $pending = $policies->filter(fn (Policy $policy) => $policy->status === PolicyStatus::Pending);

        return [
            'received_this_year' => round($policies
                ->filter(fn (Policy $policy) => $policy->commission_received_at?->greaterThanOrEqualTo($startOfYear))
                ->sum(fn (Policy $policy) => $policy->commission()), 2),
            'receivable' => round($inForce->filter(fn (Policy $policy) => $policy->commission_received_at === null)->sum(fn (Policy $policy) => $policy->commission()), 2),
            'expected' => round($pending->sum(fn (Policy $policy) => $policy->commission()), 2),
            'annual_premium_in_force' => round($inForce->sum(fn (Policy $policy) => $policy->annualPremium()), 2),
            'policies_in_force' => $inForce->count(),
            'pending_applications' => $pending->count(),
        ];
    }
}
