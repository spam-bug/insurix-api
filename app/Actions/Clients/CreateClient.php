<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\DataTransferObject\Clients\ClientData;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CreateClient
{
    /**
     * Adds a client who didn't come through the pipeline, such as one an agent already had.
     */
    public function handle(Workspace $workspace, ClientData $data): Client
    {
        $client = DB::transaction(fn () => $workspace->clients()->create($data->toArray()));

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $client;
    }
}
