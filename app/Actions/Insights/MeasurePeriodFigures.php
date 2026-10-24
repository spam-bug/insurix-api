<?php

declare(strict_types=1);

namespace App\Actions\Insights;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Enums\RenewalOutcome;
use App\Models\LeadActivity;
use App\Models\PolicyRenewal;
use App\Models\Workspace;
use Carbon\CarbonImmutable;

class MeasurePeriodFigures
{
    /**
     * The headline figures for one period: leads added, leads won (promoted) and lost, the win
     * rate (won out of won and lost), commission marked received, and the renewal rate (renewed
     * out of the outcomes recorded for renewal dates in the period). Rates are null without data.
     *
     * @return array{new_leads: int, won: int, lost: int, win_rate_percent: int|null, commission_received: float, renewals_recorded: int, renewal_rate_percent: int|null}
     */
    public function handle(Workspace $workspace, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $moments = [$start->utc(), $end->utc()];
        $days = [$start->toDateString(), $end->toDateTimeString()];

        $closings = LeadActivity::query()
            ->join('leads', 'leads.id', '=', 'lead_activities.lead_id')
            ->where('leads.workspace_id', $workspace->id)
            ->whereBetween('lead_activities.created_at', $moments)
            ->where(fn ($query) => $query
                ->where('lead_activities.type', LeadActivityType::Promoted)
                ->orWhere(fn ($query) => $query
                    ->where('lead_activities.type', LeadActivityType::StatusChanged)
                    ->where('lead_activities.properties->to', LeadStatus::Lost->value)))
            ->toBase()
            ->selectRaw('sum(case when lead_activities.type = ? then 1 else 0 end) as won, count(*) as closed', [LeadActivityType::Promoted->value])
            ->first();
        $won = (int) $closings->won;
        $closed = (int) $closings->closed;

        $outcomes = PolicyRenewal::query()
            ->where('workspace_id', $workspace->id)
            ->whereBetween('due_on', $days)
            ->pluck('outcome');
        $renewed = $outcomes->filter(fn (RenewalOutcome $outcome) => $outcome === RenewalOutcome::Renewed)->count();

        return [
            'new_leads' => $workspace->leads()->whereBetween('created_at', $moments)->count(),
            'won' => $won,
            'lost' => $closed - $won,
            'win_rate_percent' => $closed === 0 ? null : (int) round($won / $closed * 100),
            'commission_received' => round((float) $workspace->policies()->whereBetween('commission_received_at', $days)->sum('commission'), 2),
            'renewals_recorded' => $outcomes->count(),
            'renewal_rate_percent' => $outcomes->isEmpty() ? null : (int) round($renewed / $outcomes->count() * 100),
        ];
    }
}
