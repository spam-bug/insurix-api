<?php

declare(strict_types=1);

namespace App\Actions\ServiceRequests;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\ServiceRequests\ServiceRequestData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\ServiceRequestStatus;
use App\Models\Client;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateServiceRequest
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(Client $client, ServiceRequestData $data, User $author): ServiceRequest
    {
        $serviceRequest = DB::transaction(fn () => $client->serviceRequests()->create([
            ...$data->toArray(),
            'workspace_id' => $client->workspace_id,
            'user_id' => $author->id,
            'resolved_at' => $data->status === ServiceRequestStatus::Resolved ? now() : null,
        ]));

        $this->announceWorkspaceChange->handle($client->workspace_id, new WorkspaceChangeData(ChangeTopic::ServiceRequests, ChangeAction::Created, $serviceRequest->id, $serviceRequest->title));

        return $serviceRequest;
    }
}
