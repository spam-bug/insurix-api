<?php

declare(strict_types=1);

namespace App\Actions\ServiceRequests;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\ServiceRequest;
use Illuminate\Support\Facades\DB;

class DeleteServiceRequest
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    public function handle(ServiceRequest $serviceRequest): void
    {
        DB::transaction(fn () => $serviceRequest->delete());

        $this->announceWorkspaceChange->handle($serviceRequest->workspace_id, new WorkspaceChangeData(ChangeTopic::ServiceRequests, ChangeAction::Deleted, $serviceRequest->id, $serviceRequest->title));
    }
}
