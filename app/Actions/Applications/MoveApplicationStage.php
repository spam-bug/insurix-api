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
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MoveApplicationStage
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * Moves a pending application to the stage the insurer reported. Moving to the stage it's
     * already in does nothing, so the stuck-for timer doesn't restart.
     */
    public function handle(Policy $policy, ApplicationStage $stage, ?User $actor): Policy
    {
        if ($policy->status !== PolicyStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'Only a pending application can change this way.']);
        }

        $previous = $policy->application_stage;
        if ($previous === $stage) {
            return $policy;
        }

        $policy = DB::transaction(function () use ($policy, $stage, $previous, $actor) {
            $policy->update(['application_stage' => $stage, 'stage_changed_at' => now()]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::StageChanged, $actor, [
                'from' => $previous?->value,
                'to' => $stage->value,
            ]);

            return $policy;
        });

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $policy->id, $policy->product_name));

        return $policy;
    }
}
