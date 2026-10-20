<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\LeadData;
use App\Enums\LeadActivityType;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateLead
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    public function handle(Lead $lead, LeadData $data, User $actor): Lead
    {
        $lead->fill($data->toAttributes());
        $changedFields = array_keys($lead->getDirty());

        if ($changedFields === []) {
            return $lead;
        }

        return DB::transaction(function () use ($lead, $actor, $changedFields) {
            $lead->save();

            $this->recordLeadActivity->handle($lead, LeadActivityType::Updated, $actor, properties: ['fields' => $changedFields]);

            return $lead;
        });
    }
}
