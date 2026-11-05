<?php

declare(strict_types=1);

namespace App\Actions\Dashboard;

use App\Actions\Insights\ChartCommissionByMonth;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class ChartDashboardTrends
{
    public function __construct(private ChartCommissionByMonth $chartCommissionByMonth) {}

    /**
     * New leads and commission received in each of the last $months calendar months (in the
     * workspace's timezone), oldest first and ending with this month, for the dashboard's sparklines.
     * Also the same figures for last month up to today's day of the month, so this month so far is
     * compared like for like instead of against a whole month.
     *
     * @return array{months: array<int, string>, new_leads: array<int, int>, commission_received: array<int, float>, new_leads_last_month_to_date: int, commission_received_last_month_to_date: float}
     */
    public function handle(Workspace $workspace, int $months = 6): array
    {
        $today = CarbonImmutable::now($workspace->timezone);
        $thisMonth = $today->startOfMonth();

        return Cache::tags("workspace:{$workspace->id}")->rememberForever("dashboard:{$workspace->id}:trends:{$today->format('Y-m-d')}", function () use ($workspace, $months, $thisMonth, $today) {
            $monthStarts = collect(range($months - 1, 0))->map(fn (int $monthsAgo) => $thisMonth->subMonthsNoOverflow($monthsAgo));

            $columns = $monthStarts->map(fn (CarbonImmutable $start, int $index) => "count(case when created_at >= ? and created_at < ? then 1 end) as month_{$index}");
            $bindings = $monthStarts->flatMap(fn (CarbonImmutable $start) => [$start->utc(), $start->addMonth()->utc()]);

            $newLeads = (array) $workspace->leads()->toBase()->selectRaw($columns->implode(', '), $bindings->all())->first();
            $commission = $this->chartCommissionByMonth->handle($workspace, $thisMonth, $months);
            $lastMonth = $thisMonth->subMonthNoOverflow();
            $sameDayLastMonth = $today->subMonthNoOverflow()->endOfDay();

            return [
                'months' => $monthStarts->map(fn (CarbonImmutable $start) => $start->format('Y-m'))->all(),
                'new_leads' => array_map(intval(...), array_values($newLeads)),
                'commission_received' => array_column($commission, 'received'),
                'new_leads_last_month_to_date' => $workspace->leads()->whereBetween('created_at', [$lastMonth->utc(), $sameDayLastMonth->utc()])->count(),
                'commission_received_last_month_to_date' => round((float) $workspace->policies()->whereBetween('commission_received_at', [$lastMonth->toDateString(), $sameDayLastMonth->toDateString()])->sum('commission'), 2),
            ];
        });
    }
}
