<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\ApplicationRequirement;
use Illuminate\Support\Facades\DB;

class DeleteRequirement
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(ApplicationRequirement $requirement): void
    {
        DB::transaction(fn () => $requirement->delete());

        $this->announceWorkspaceChange->handle($requirement->policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $requirement->policy->id, $requirement->policy->product_name));
    }
}
