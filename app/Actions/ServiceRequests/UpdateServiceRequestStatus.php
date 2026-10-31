<?php

declare(strict_types=1);

namespace App\Actions\ServiceRequests;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

class UpdateServiceRequestStatus
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(ServiceRequest $serviceRequest, ServiceRequestStatus $status): ServiceRequest
    {
        DB::transaction(fn () => $serviceRequest->update([
            'status' => $status,
            'resolved_at' => UpdateServiceRequest::resolvedAt($serviceRequest, $status),
        ]));

        $this->announceWorkspaceChange->handle($serviceRequest->workspace_id, new WorkspaceChangeData(ChangeTopic::ServiceRequests, ChangeAction::Updated, $serviceRequest->id, $serviceRequest->title));

        return $serviceRequest;
    }
}
