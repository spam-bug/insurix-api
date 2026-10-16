<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;

class RecordLeadActivity
{
    /**
     * @param  array<string, mixed>|null  $properties
     */
    public function handle(Lead $lead, LeadActivityType $type, ?User $actor = null, ?string $body = null, ?array $properties = null): LeadActivity
    {
        return $lead->activities()->create([
            'user_id' => $actor?->id,
            'type' => $type,
            'body' => $body,
            'properties' => $properties,
        ]);
    }
}
