<?php

namespace App\Actions\Policies;

use App\Enums\PolicyEventType;
use App\Models\Policy;
use App\Models\PolicyEvent;
use App\Models\User;

class RecordPolicyEvent
{
    /**
     * Adds an entry to the policy's timeline.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public function handle(Policy $policy, PolicyEventType $type, ?User $actor, ?array $properties = null): PolicyEvent
    {
        return $policy->events()->create([
            'user_id' => $actor?->id,
            'type' => $type,
            'properties' => $properties,
        ]);
    }
}
