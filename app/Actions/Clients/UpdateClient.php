<?php

namespace App\Actions\Clients;

use App\DataTransferObjects\Clients\ClientData;
use App\Models\Client;

class UpdateClient
{
    public function handle(Client $client, ClientData $data): Client
    {
        $client->update($data->toAttributes());

        return $client;
    }
}
