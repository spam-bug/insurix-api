<?php

namespace App\Actions\Applications;

use App\Actions\Policies\RecordPolicyEvent;
use App\Enums\PolicyEventType;
use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeclineApplication
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * The insurer turned the application down. The reason goes on the timeline.
     */
    public function handle(Policy $policy, ?string $reason, ?User $actor): Policy
    {
        return DB::transaction(function () use ($policy, $reason, $actor) {
            $policy->update(['status' => PolicyStatus::Declined, 'application_stage' => null, 'stage_changed_at' => null]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::Declined, $actor, ['reason' => $reason]);

            return $policy;
        });
    }
}
