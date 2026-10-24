<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateLeadStatus
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

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

        Cache::tags("workspace:{$lead->workspace_id}")->flush();

        return $lead;
    }
}
