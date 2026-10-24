<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Policies\PolicyData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Policy;
use Illuminate\Support\Facades\DB;

class UpdatePolicy
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private PreparePolicyAttributes $preparePolicyAttributes) {}

    public function handle(Policy $policy, PolicyData $data): Policy
    {
        $attributes = $this->preparePolicyAttributes->handle($policy->workspace, $data, $policy);

        DB::transaction(fn () => $policy->update($attributes));

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $policy->id, $policy->product_name));

        return $policy;
    }
}
