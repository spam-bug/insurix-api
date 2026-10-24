<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Clients\ClientData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateClient
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Adds a client who didn't come through the pipeline, such as one an agent already had.
     */
    public function handle(Workspace $workspace, ClientData $data): Client
    {
        $client = DB::transaction(fn () => $workspace->clients()->create($data->toArray()));

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Clients, ChangeAction::Created, $client->id, $client->name));

        return $client;
    }
}
