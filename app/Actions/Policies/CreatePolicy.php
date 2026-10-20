<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\DataTransferObject\Policies\PolicyData;
use App\Enums\PolicyEventType;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreatePolicy
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * Records a policy for the client, owned by the client's agent. A pending one starts as a
     * submitted application.
     */
    public function handle(Client $client, PolicyData $data, ?User $actor = null): Policy
    {
        return DB::transaction(function () use ($client, $data, $actor) {
            $policy = $client->policies()->create([
                ...$data->toAttributes(),
                'user_id' => $client->user_id,
            ]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::Recorded, $actor, ['status' => $policy->status->value]);

            return $policy;
        });
    }
}
