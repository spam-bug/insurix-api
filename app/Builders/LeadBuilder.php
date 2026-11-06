<?php

declare(strict_types=1);

namespace App\Builders;

use App\Enums\LeadActivityType;
use App\Models\Lead;
use Illuminate\Database\Eloquent\Builder;

/**
 * @extends Builder<Lead>
 */
class LeadBuilder extends Builder
{
    /**
     * Adds what the agent needs to know about the next step: the earliest follow-up still to do
     * (`next_follow_up_at`) and the last time they were in touch (`last_contacted_at`).
     */
    public function withNextStep(): static
    {
        return $this
            ->withMin(['followUps as next_follow_up_at' => fn (Builder $query) => $query->whereNull('completed_at')], 'due_at')
            ->withMax(['activities as last_contacted_at' => fn (Builder $query) => $query->whereIn('type', LeadActivityType::contacts())], 'created_at');
    }
}
