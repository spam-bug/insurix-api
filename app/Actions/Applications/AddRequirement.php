<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ApplicationStage;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\PolicyEventType;
use App\Enums\PolicyStatus;
use App\Models\ApplicationRequirement;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AddRequirement
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private MoveApplicationStage $moveApplicationStage,
        private RecordPolicyEvent $recordPolicyEvent,
    ) {}

    /**
     * Adds something the insurer needs to the application's checklist. The insurer asking for
     * it means the application is waiting on requirements, so the stage moves there too.
     */
    public function handle(Policy $policy, string $title, ?User $actor): ApplicationRequirement
    {
        if ($policy->status !== PolicyStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'Only a pending application can change this way.']);
        }

        $requirement = DB::transaction(function () use ($policy, $title, $actor) {
            $requirement = $policy->requirements()->create(['title' => $title]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::RequirementAdded, $actor, ['title' => $title]);
            $this->moveApplicationStage->handle($policy, ApplicationStage::RequirementsNeeded, $actor);

            return $requirement;
        });

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $policy->id, $policy->product_name));

        return $requirement;
    }
}
