<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Policy;
use Illuminate\Support\Facades\DB;

class DeletePolicy
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(Policy $policy): void
    {
        DB::transaction(fn () => $policy->delete());

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Deleted, $policy->id, $policy->product_name));
    }
}
