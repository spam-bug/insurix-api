<?php

namespace App\Actions\Insights;

use App\Enums\PolicyStatus;
use App\Models\User;
use Carbon\CarbonImmutable;

class MeasureRetention
{
    /** Renewals this many days out count as coming up. */
    public const RENEWAL_WINDOW_DAYS = 60;

    /**
     * "Do clients keep their policies?": of the policies that were ever in force (in force,
     * lapsed or cancelled; pending applications aren't counted), the share still in force.
     * Null when there are none yet. Also how many in-force policies renew soon.
     *
     * @return array{in_force: int, lapsed: int, cancelled: int, retention_percent: int|null, renewals_coming_up: int}
     */
    public function handle(User $agent): array
    {
        $counts = $agent->policies()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $inForce = (int) ($counts[PolicyStatus::InForce->value] ?? 0);
        $lapsed = (int) ($counts[PolicyStatus::Lapsed->value] ?? 0);
        $cancelled = (int) ($counts[PolicyStatus::Cancelled->value] ?? 0);
        $everInForce = $inForce + $lapsed + $cancelled;

        $today = CarbonImmutable::now($agent->timezone)->startOfDay();

        return [
            'in_force' => $inForce,
            'lapsed' => $lapsed,
            'cancelled' => $cancelled,
            'retention_percent' => $everInForce === 0 ? null : (int) round($inForce / $everInForce * 100),
            'renewals_coming_up' => $agent->policies()
                ->where('status', PolicyStatus::InForce)
                ->whereBetween('renewal_date', [$today->toDateString(), $today->addDays(self::RENEWAL_WINDOW_DAYS)->toDateString()])
                ->count(),
        ];
    }
}
