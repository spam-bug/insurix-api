<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Actions\Policies\FindUpcomingRenewal;
use App\Enums\PolicyStatus;
use App\Enums\RenewalOutcome;
use App\Models\Policy;
use App\Models\PolicyRenewal;
use App\Models\Workspace;
use Carbon\CarbonImmutable;

class MeasureRetention
{
    public function __construct(private FindUpcomingRenewal $findUpcomingRenewal) {}

    /** Renewals this many days out count as coming up. */
    public const RENEWAL_WINDOW_DAYS = 60;

    /**
     * "Do clients keep their policies?": of the policies that were ever in force (in force,
     * lapsed or cancelled; pending applications aren't counted), the share still in force.
     * Null when there are none yet. Also how many in-force policies renew soon, and the renewal
     * rate: renewed out of every outcome recorded for renewal dates in the last 12 months.
     *
     * @return array{in_force: int, lapsed: int, cancelled: int, retention_percent: int|null, renewals_coming_up: int, renewals_recorded: int, renewal_rate_percent: int|null}
     */
    public function handle(Workspace $workspace): array
    {
        $counts = $workspace->policies()
            ->toBase()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $inForce = (int) ($counts[PolicyStatus::InForce->value] ?? 0);
        $lapsed = (int) ($counts[PolicyStatus::Lapsed->value] ?? 0);
        $cancelled = (int) ($counts[PolicyStatus::Cancelled->value] ?? 0);
        $everInForce = $inForce + $lapsed + $cancelled;

        $today = CarbonImmutable::now($workspace->timezone)->startOfDay();

        $outcomes = PolicyRenewal::query()
            ->where('workspace_id', $workspace->id)
            ->whereBetween('due_on', [$today->subYear()->toDateString(), $today->endOfDay()->toDateTimeString()])
            ->pluck('outcome');
        $renewed = $outcomes->filter(fn (RenewalOutcome $outcome) => $outcome === RenewalOutcome::Renewed)->count();

        return [
            'in_force' => $inForce,
            'lapsed' => $lapsed,
            'cancelled' => $cancelled,
            'retention_percent' => $everInForce === 0 ? null : (int) round($inForce / $everInForce * 100),
            'renewals_coming_up' => $workspace->policies()
                ->where('status', PolicyStatus::InForce)
                ->get()
                ->filter(fn (Policy $policy) => $this->findUpcomingRenewal->handle($policy, $today, self::RENEWAL_WINDOW_DAYS))
                ->count(),
            'renewals_recorded' => $outcomes->count(),
            'renewal_rate_percent' => $outcomes->isEmpty() ? null : (int) round($renewed / $outcomes->count() * 100),
        ];
    }
}
