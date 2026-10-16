<?php

namespace App\Actions\Leads;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateLeadStatus
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    public function handle(Lead $lead, LeadStatus $status, User $actor): Lead
    {
        $previous = $lead->status;

        if ($previous === $status) {
            return $lead;
        }

        return DB::transaction(function () use ($lead, $status, $previous, $actor) {
            $lead->update(['status' => $status]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::StatusChanged, $actor, properties: [
                'from' => $previous->value,
                'to' => $status->value,
            ]);

            return $lead;
        });
    }
}
