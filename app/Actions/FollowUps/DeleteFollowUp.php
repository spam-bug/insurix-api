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

class DeleteFollowUp
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private ScoreLead $scoreLead) {}

    public function handle(FollowUp $followUp): void
    {
        DB::transaction(function () use ($followUp) {
            $lead = $followUp->lead;

            $followUp->delete();

            if ($lead) {
                $this->scoreLead->handle($lead);
            }
        });

        $this->announceWorkspaceChange->handle($followUp->workspace_id, new WorkspaceChangeData(ChangeTopic::FollowUps, ChangeAction::Deleted, $followUp->id, $followUp->title));
    }
}
