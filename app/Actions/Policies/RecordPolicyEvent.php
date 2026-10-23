<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyEventType;
use App\Models\Policy;
use App\Models\PolicyEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RecordPolicyEvent
{
    /**
     * Adds an entry to the policy's timeline.
     *
     * @param  array<string, mixed>|null  $properties
     */
    public function handle(Policy $policy, PolicyEventType $type, ?User $actor, ?array $properties = null): PolicyEvent
    {
        return DB::transaction(fn () => $policy->events()->create([
            'user_id' => $actor?->id,
            'type' => $type,
            'properties' => $properties,
        ]));
    }
}
