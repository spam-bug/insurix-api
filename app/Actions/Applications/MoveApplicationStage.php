<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Policies\RecordPolicyEvent;
use App\Enums\ApplicationStage;
use App\Enums\PolicyEventType;
use App\Models\Policy;
use App\Models\User;

class MoveApplicationStage
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * Moves a pending application to the stage the insurer reported. Moving to the stage it's
     * already in does nothing, so the stuck-for timer doesn't restart.
     */
    public function handle(Policy $policy, ApplicationStage $stage, ?User $actor): Policy
    {
        $previous = $policy->application_stage;
        if ($previous === $stage) {
            return $policy;
        }

        $policy->update(['application_stage' => $stage, 'stage_changed_at' => now()]);

        $this->recordPolicyEvent->handle($policy, PolicyEventType::StageChanged, $actor, [
            'from' => $previous?->value,
            'to' => $stage->value,
        ]);

        return $policy;
    }
}
