<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\FollowUp;
use Illuminate\Support\Facades\DB;

class UpdateFollowUp
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(FollowUp $followUp, FollowUpData $data): FollowUp
    {
        DB::transaction(fn () => $followUp->update($data->toArray()));

        $this->announceWorkspaceChange->handle($followUp->workspace_id, new WorkspaceChangeData(ChangeTopic::FollowUps, ChangeAction::Updated, $followUp->id, $followUp->title));

        return $followUp;
    }
}
