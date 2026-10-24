<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

class DeleteLead
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(Lead $lead): void
    {
        DB::transaction(fn () => $lead->delete());

        $this->announceWorkspaceChange->handle($lead->workspace_id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Deleted, $lead->id, $lead->name));
    }
}
