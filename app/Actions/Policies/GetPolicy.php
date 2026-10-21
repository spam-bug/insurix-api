<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Policy;
use Illuminate\Support\Facades\Cache;

class GetPolicy
{
    /**
     * The policy with everything its detail page shows: client, checklist, renewals and timeline.
     */
    public function handle(Policy $policy): Policy
    {
        return Cache::tags("agent:{$policy->user_id}")->rememberForever("policies:{$policy->user_id}:{$policy->id}", fn () => $policy->load(PolicyResource::DETAIL_RELATIONS));
    }
}
