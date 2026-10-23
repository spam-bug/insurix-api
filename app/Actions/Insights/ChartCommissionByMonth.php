<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Policy;
use App\Models\User;
use Carbon\CarbonImmutable;

class ChartCommissionByMonth
{
    /**
     * Commission marked received in each of the last $months calendar months, oldest first,
     * this month included. Empty months are listed with zero.
     *
     * @return array<int, array{month: string, received: float, policies: int}>
     */
    public function handle(User $agent, int $months = 12): array
    {
        $firstMonth = CarbonImmutable::now($agent->timezone)->startOfMonth()->subMonths($months - 1);

        $byMonth = $agent->policies()
            ->where('commission_received_at', '>=', $firstMonth->toDateString())
            ->get()
            ->groupBy(fn (Policy $policy) => $policy->commission_received_at->format('Y-m'));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($firstMonth, $byMonth) {
                $month = $firstMonth->addMonths($offset)->format('Y-m');
                $policies = $byMonth->get($month, collect());

                return [
                    'month' => $month,
                    'received' => round($policies->sum(fn (Policy $policy) => $policy->commission()), 2),
                    'policies' => $policies->count(),
                ];
            })
            ->all();
    }
}
