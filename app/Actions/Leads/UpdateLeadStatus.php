<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateLeadStatus
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordLeadActivity $recordLeadActivity) {}

    /**
     * A promoted lead's stage is settled; its client is where the work continues.
     */
    public function handle(Lead $lead, LeadStatus $status, User $actor): Lead
    {
        if ($lead->status === LeadStatus::Converted) {
            throw ValidationException::withMessages(['status' => 'This lead is already a client.']);
        }

        $previous = $lead->status;

        if ($previous === $status) {
            return $lead;
        }

        $lead = DB::transaction(function () use ($lead, $status, $previous, $actor) {
            $lead->update(['status' => $status]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::StatusChanged, $actor, properties: [
                'from' => $previous->value,
                'to' => $status->value,
            ]);

            return $lead;
        });

        $this->announceWorkspaceChange->handle($lead->workspace_id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Updated, $lead->id, $lead->name));

        return $lead;
    }
}
