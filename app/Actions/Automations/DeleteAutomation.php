<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Automation;
use Illuminate\Support\Facades\DB;

class DeleteAutomation
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** What it already did (follow-ups, notes, stage changes) stays; only the rule and its run history go. */
    public function handle(Automation $automation): void
    {
        DB::transaction(fn () => $automation->delete());

        $this->announceWorkspaceChange->handle($automation->workspace_id, new WorkspaceChangeData(ChangeTopic::Automations, ChangeAction::Deleted, $automation->id, $automation->name));
    }
}
