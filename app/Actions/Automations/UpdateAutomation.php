<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Automations\AutomationData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Automation;
use Illuminate\Support\Facades\DB;

class UpdateAutomation
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(Automation $automation, AutomationData $data): Automation
    {
        DB::transaction(fn () => $automation->update($data->toArray()));

        $this->announceWorkspaceChange->handle($automation->workspace_id, new WorkspaceChangeData(ChangeTopic::Automations, ChangeAction::Updated, $automation->id, $automation->name));

        return $automation;
    }
}
