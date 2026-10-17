<?php

namespace App\Actions\FollowUps;

use App\Actions\Leads\RecordLeadActivity;
use App\Enums\LeadActivityType;
use App\Models\FollowUp;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompleteFollowUp
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    /**
     * Marks the follow-up done and logs it on the lead, so the history shows what was done when.
     */
    public function handle(FollowUp $followUp, User $actor): FollowUp
    {
        if ($followUp->completed_at !== null) {
            return $followUp;
        }

        return DB::transaction(function () use ($followUp, $actor) {
            $followUp->update(['completed_at' => now()]);

            $this->recordLeadActivity->handle($followUp->lead, LeadActivityType::FollowUpCompleted, $actor, properties: [
                'follow_up_id' => $followUp->id,
                'follow_up_type' => $followUp->type->value,
                'title' => $followUp->title,
            ]);

            return $followUp;
        });
    }
}
