<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Policies\PolicyData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\PolicyEventType;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePolicy
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private PreparePolicyAttributes $preparePolicyAttributes,
        private RecordPolicyEvent $recordPolicyEvent,
    ) {}

    /**
     * Records a policy for the client, owned by the client's agent. A pending one starts as a
     * submitted application.
     */
    public function handle(Client $client, PolicyData $data, ?User $actor = null): Policy
    {
        $attributes = $this->preparePolicyAttributes->handle($client->workspace, $data);

        $policy = DB::transaction(function () use ($client, $attributes, $actor) {
            $policy = $client->policies()->create([
                ...$attributes,
                'workspace_id' => $client->workspace_id,
            ]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::Recorded, $actor, ['status' => $policy->status->value]);

            return $policy;
        });

        $this->announceWorkspaceChange->handle($client->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Created, $policy->id, $policy->product_name));

        return $policy;
    }
}
