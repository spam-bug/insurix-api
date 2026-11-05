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
     *
     * @return array{months: array<int, string>, new_leads: array<int, int>, commission_received: array<int, float>}
     */
    public function handle(Workspace $workspace, int $months = 6): array
    {
        $thisMonth = CarbonImmutable::now($workspace->timezone)->startOfMonth();

        return Cache::tags("workspace:{$workspace->id}")->rememberForever("dashboard:{$workspace->id}:trends:{$thisMonth->format('Y-m')}", function () use ($workspace, $months, $thisMonth) {
            $monthStarts = collect(range($months - 1, 0))->map(fn (int $monthsAgo) => $thisMonth->subMonthsNoOverflow($monthsAgo));

            $columns = $monthStarts->map(fn (CarbonImmutable $start, int $index) => "count(case when created_at >= ? and created_at < ? then 1 end) as month_{$index}");
            $bindings = $monthStarts->flatMap(fn (CarbonImmutable $start) => [$start->utc(), $start->addMonth()->utc()]);

            $newLeads = (array) $workspace->leads()->toBase()->selectRaw($columns->implode(', '), $bindings->all())->first();
            $commission = $this->chartCommissionByMonth->handle($workspace, $thisMonth, $months);

            return [
                'months' => $monthStarts->map(fn (CarbonImmutable $start) => $start->format('Y-m'))->all(),
                'new_leads' => array_map(intval(...), array_values($newLeads)),
                'commission_received' => array_column($commission, 'received'),
            ];
        });
    }
}
