<?php

namespace App\Actions\Renewals;

use App\Actions\Policies\RecordPolicyEvent;
use App\Enums\PolicyEventType;
use App\Enums\RenewalOutcome;
use App\Enums\RenewalTerm;
use App\Models\Policy;
use App\Models\PolicyRenewal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class RecordRenewalOutcome
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * Records what happened at a renewal date and updates the policy to match: still in force,
     * lapsed, or cancelled. A renewed custom-date policy moves on to its next renewal date.
     */
    public function handle(
        Policy $policy,
        CarbonImmutable $dueOn,
        RenewalOutcome $outcome,
        ?CarbonImmutable $nextRenewalDate,
        ?string $notes,
        User $actor,
    ): PolicyRenewal {
        return DB::transaction(function () use ($policy, $dueOn, $outcome, $nextRenewalDate, $notes, $actor) {
            $renewal = $policy->renewals()->create([
                'user_id' => $actor->id,
                'due_on' => $dueOn,
                'outcome' => $outcome,
                'notes' => $notes,
            ]);

            $policy->update([
                'status' => $outcome->policyStatus(),
                'renewal_date' => $outcome === RenewalOutcome::Renewed && $policy->renewal_term === RenewalTerm::Custom
                    ? $nextRenewalDate
                    : $policy->renewal_date,
            ]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::RenewalRecorded, $actor, [
                'due_on' => $dueOn->toDateString(),
                'outcome' => $outcome->value,
                'next_renewal_date' => $nextRenewalDate?->toDateString(),
            ]);

            return $renewal;
        });
    }
}
