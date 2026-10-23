<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Models\Workspace;
use Carbon\CarbonImmutable;

class ChartCommissionByMonth
{
    /**
     * Commission marked received in each of the last $months calendar months, oldest first,
     * this month included. Empty months are listed with zero.
     *
     * @return array<int, array{month: string, received: float, policies: int}>
     */
    public function handle(Workspace $workspace, int $months = 12): array
    {
        $firstMonth = CarbonImmutable::now($workspace->timezone)->startOfMonth()->subMonths($months - 1);

        $byMonth = $workspace->policies()
            ->toBase()
            ->where('commission_received_at', '>=', $firstMonth->toDateString())
            ->selectRaw('commission_received_at, sum(commission) as received, count(*) as policies')
            ->groupBy('commission_received_at')
            ->get()
            ->groupBy(fn (object $day) => substr((string) $day->commission_received_at, 0, 7));

        return collect(range(0, $months - 1))
            ->map(function (int $offset) use ($firstMonth, $byMonth) {
                $month = $firstMonth->addMonths($offset)->format('Y-m');
                $days = $byMonth->get($month, collect());

                return [
                    'month' => $month,
                    'received' => round((float) $days->sum('received'), 2),
                    'policies' => (int) $days->sum('policies'),
                ];
            })
            ->all();
    }
}
