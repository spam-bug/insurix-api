<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Automations\AutomationData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Automation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateAutomation
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(Workspace $workspace, AutomationData $data, User $author): Automation
    {
        $automation = DB::transaction(fn () => $workspace->automations()->create([
            ...$data->toArray(),
            'user_id' => $author->id,
        ]));

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Automations, ChangeAction::Created, $automation->id, $automation->name));

        return $automation;
    }
}
