<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Policies\RecordPolicyEvent;
use App\Enums\ApplicationStage;
use App\Enums\PolicyEventType;
use App\Models\ApplicationRequirement;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddRequirement
{
    public function __construct(
        private MoveApplicationStage $moveApplicationStage,
        private RecordPolicyEvent $recordPolicyEvent,
    ) {}

    /**
     * Adds something the insurer needs to the application's checklist. The insurer asking for
     * it means the application is waiting on requirements, so the stage moves there too.
     */
    public function handle(Policy $policy, string $title, ?User $actor): ApplicationRequirement
    {
        return DB::transaction(function () use ($policy, $title, $actor) {
            $requirement = $policy->requirements()->create(['title' => $title]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::RequirementAdded, $actor, ['title' => $title]);
            $this->moveApplicationStage->handle($policy, ApplicationStage::RequirementsNeeded, $actor);

            return $requirement;
        });
    }
}
