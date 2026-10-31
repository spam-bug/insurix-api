<?php

declare(strict_types=1);

namespace App\Actions\ServiceRequests;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\ServiceRequests\ServiceRequestData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\ServiceRequestStatus;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

class UpdateServiceRequest
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** Saves the full request. Resolving it stamps when; reopening clears that. */
    public function handle(ServiceRequest $serviceRequest, ServiceRequestData $data): ServiceRequest
    {
        DB::transaction(fn () => $serviceRequest->update([
            ...$data->toArray(),
            'resolved_at' => self::resolvedAt($serviceRequest, $data->status),
        ]));

        $this->announceWorkspaceChange->handle($serviceRequest->workspace_id, new WorkspaceChangeData(ChangeTopic::ServiceRequests, ChangeAction::Updated, $serviceRequest->id, $serviceRequest->title));

        return $serviceRequest;
    }

    /** Keeps the first time it was resolved, so editing a resolved request doesn't move it in the history. */
    public static function resolvedAt(ServiceRequest $serviceRequest, ServiceRequestStatus $status): mixed
    {
        return $status === ServiceRequestStatus::Resolved ? ($serviceRequest->resolved_at ?? now()) : null;
    }
}
