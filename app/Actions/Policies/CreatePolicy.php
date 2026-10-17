<?php

namespace App\Actions\Policies;

use App\DataTransferObjects\Policies\PolicyData;
use App\Models\Client;
use App\Models\Policy;

class CreatePolicy
{
    /**
     * Records a policy for the client, owned by the client's agent.
     */
    public function handle(Client $client, PolicyData $data): Policy
    {
        return $client->policies()->create([
            ...$data->toAttributes(),
            'user_id' => $client->user_id,
        ]);
    }
}
