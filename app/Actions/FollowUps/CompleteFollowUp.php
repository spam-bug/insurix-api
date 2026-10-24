<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\RecordLeadActivity;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteFollowUp
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordLeadActivity $recordLeadActivity) {}

    /**
     * Marks the follow-up done and logs it on the lead, so the history shows what was done when.
     * A client without a lead shows its completed follow-ups in its history directly.
     */
    public function handle(FollowUp $followUp, User $actor): FollowUp
    {
        if ($followUp->completed_at !== null) {
            return $followUp;
        }

        $followUp = DB::transaction(function () use ($followUp, $actor) {
            $followUp->update(['completed_at' => now()]);

            if ($followUp->lead) {
                $this->recordLeadActivity->handle($followUp->lead, LeadActivityType::FollowUpCompleted, $actor, properties: [
                    'follow_up_id' => $followUp->id,
                    'follow_up_type' => $followUp->type->value,
                    'title' => $followUp->title,
                ]);
            }

            return $followUp;
        });

        $this->announceWorkspaceChange->handle($followUp->workspace_id, new WorkspaceChangeData(ChangeTopic::FollowUps, ChangeAction::Updated, $followUp->id, $followUp->title));

        return $followUp;
    }
}
