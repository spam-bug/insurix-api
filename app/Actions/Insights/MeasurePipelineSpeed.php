<?php

namespace App\Actions\Insights;

use App\Enums\LeadActivityType;
use App\Models\LeadActivity;
use App\Models\User;
use Carbon\CarbonImmutable;

class MeasurePipelineSpeed
{
    /**
     * "How long does a lead take to become a client?": days from a lead being added to its
     * promotion, over leads promoted in the last 12 months. Null days when none were.
     *
     * @return array{average_days: int|null, median_days: int|null, converted: int}
     */
    public function handle(User $agent): array
    {
        $days = LeadActivity::query()
            ->join('leads', 'leads.id', '=', 'lead_activities.lead_id')
            ->where('leads.user_id', $agent->id)
            ->where('lead_activities.type', LeadActivityType::Promoted)
            ->where('lead_activities.created_at', '>=', CarbonImmutable::now()->subYear())
            ->toBase()
            ->get(['leads.created_at as added_at', 'lead_activities.created_at as promoted_at'])
            ->map(fn (object $row) => (int) CarbonImmutable::parse($row->added_at)->diffInDays(CarbonImmutable::parse($row->promoted_at)))
            ->sort()
            ->values();

        return [
            'average_days' => $days->isEmpty() ? null : (int) round($days->avg()),
            'median_days' => $days->isEmpty() ? null : (int) round($days->median()),
            'converted' => $days->count(),
        ];
    }
}
