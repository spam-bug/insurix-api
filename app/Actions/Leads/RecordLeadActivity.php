<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;

class RecordLeadActivity
{
    public function __construct(private ScoreLead $scoreLead) {}

    /**
     * Logs what happened to the lead. Every change to a lead passes through here, so it's also
     * where the lead's score is brought up to date.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public function handle(Lead $lead, LeadActivityType $type, ?User $actor = null, ?string $body = null, ?array $properties = null): LeadActivity
    {
        $activity = $lead->activities()->create([
            'user_id' => $actor?->id,
            'type' => $type,
            'body' => $body,
            'properties' => $properties,
        ]);

        $this->scoreLead->handle($lead);

        return $activity;
    }
}
