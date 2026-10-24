<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Leads\LeadData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateLead
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange, private RecordLeadActivity $recordLeadActivity) {}

    public function handle(Lead $lead, LeadData $data, User $actor): Lead
    {
        $lead->fill($data->toArray());
        $changedFields = array_keys($lead->getDirty());

        if ($changedFields === []) {
            return $lead;
        }

        $lead = DB::transaction(function () use ($lead, $actor, $changedFields) {
            $lead->save();

            $this->recordLeadActivity->handle($lead, LeadActivityType::Updated, $actor, properties: ['fields' => $changedFields]);

            return $lead;
        });

        $this->announceWorkspaceChange->handle($lead->workspace_id, new WorkspaceChangeData(ChangeTopic::Leads, ChangeAction::Updated, $lead->id, $lead->name));

        return $lead;
    }
}
