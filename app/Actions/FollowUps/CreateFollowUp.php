<?php

declare(strict_types=1);

namespace App\Actions\FollowUps;

use App\Actions\Leads\ScoreLead;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Policy;
use Illuminate\Support\Facades\DB;

class CreateFollowUp
{
    public function __construct(private ScoreLead $scoreLead) {}

    /**
     * Adds a follow-up for the lead, owned by the lead's agent. A follow-up already past due
     * lowers the lead's score, so the score is brought up to date.
     * Reminders created from a policy carry the policy and a key, so each is only made once.
     */
    public function handle(Lead $lead, FollowUpData $data, ?Policy $policy = null, ?string $reminderKey = null): FollowUp
    {
        return DB::transaction(function () use ($lead, $data, $policy, $reminderKey) {
            $followUp = $lead->followUps()->create([
                ...$data->toArray(),
                'policy_id' => $policy?->id,
                'reminder_key' => $reminderKey,
                'user_id' => $lead->user_id,
            ]);

            $this->scoreLead->handle($lead);

            return $followUp;
        });
    }
}
