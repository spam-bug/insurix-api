<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class SummarizeCommissions
{
    /**
     * "How much commission am I earning or expecting?":
     *  - received_this_year: what the agent recorded as received this calendar year
     *  - receivable: the estimate on premiums already paid, where nothing was received yet
     *  - expected: first-year commission on pending applications, if they're approved
     *
     * @return array{received_this_year: float, receivable: float, expected: float, annual_premium_in_force: float, policies_in_force: int, pending_applications: int}
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->remember("policies:{$workspace->id}:commissions", now()->addMinutes(5), function () use ($workspace) {
            $totals = $workspace->policies()->toBase()->selectRaw(
                'coalesce(sum(case when status = ? then commission end), 0) as expected,
                coalesce(sum(case when status = ? then annual_premium end), 0) as annual_premium_in_force,
                count(case when status = ? then 1 end) as policies_in_force,
                count(case when status = ? then 1 end) as pending_applications',
                [
                    PolicyStatus::Pending->value,
                    PolicyStatus::InForce->value,
                    PolicyStatus::InForce->value,
                    PolicyStatus::Pending->value,
                ],
            )->first();

            $payments = $workspace->policyPayments()->toBase()->selectRaw(
                'coalesce(sum(case when commission_received_on >= ? then commission_received end), 0) as received_this_year,
                coalesce(sum(case when commission_received is null then commission_expected end), 0) as receivable',
                [CarbonImmutable::now($workspace->timezone)->startOfYear()->toDateString()],
            )->first();

            return [
                'received_this_year' => round((float) $payments->received_this_year, 2),
                'receivable' => round((float) $payments->receivable, 2),
                'expected' => round((float) $totals->expected, 2),
                'annual_premium_in_force' => round((float) $totals->annual_premium_in_force, 2),
                'policies_in_force' => (int) $totals->policies_in_force,
                'pending_applications' => (int) $totals->pending_applications,
            ];
        });
    }
}
