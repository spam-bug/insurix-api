<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Clients\ClientData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Client;
use Illuminate\Support\Facades\DB;

class UpdateClient
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(Client $client, ClientData $data): Client
    {
        DB::transaction(fn () => $client->update($data->toArray()));

        $this->announceWorkspaceChange->handle($client->workspace_id, new WorkspaceChangeData(ChangeTopic::Clients, ChangeAction::Updated, $client->id, $client->name));

        return $client;
    }
}
