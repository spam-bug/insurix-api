<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\DataTransferObject\Clients\ClientData;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class UpdateClient
{
    public function handle(Client $client, ClientData $data): Client
    {
        DB::transaction(fn () => $client->update($data->toAttributes()));

        return $client;
    }
}
