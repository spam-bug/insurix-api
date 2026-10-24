<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\FollowUp;
use Illuminate\Support\Facades\DB;

class ReopenFollowUp
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private ScoreLead $scoreLead) {}

    /**
     * Undoes a completion, for a follow-up ticked off by mistake. The history entry stays.
     */
    public function handle(FollowUp $followUp): FollowUp
    {
        DB::transaction(function () use ($followUp) {
            $followUp->update(['completed_at' => null]);

            if ($followUp->lead) {
                $this->scoreLead->handle($followUp->lead);
            }
        });

        $this->announceWorkspaceChange->handle($followUp->workspace_id, new WorkspaceChangeData(ChangeTopic::FollowUps, ChangeAction::Updated, $followUp->id, $followUp->title));

        return $followUp;
    }
}
