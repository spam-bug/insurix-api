<?php

declare(strict_types=1);

namespace App\Actions\Renewals;

use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Renewals\RenewalData;
use App\Enums\PolicyEventType;
use App\Enums\RenewalOutcome;
use App\Enums\RenewalTerm;
use App\Models\Policy;
use App\Models\PolicyRenewal;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordRenewalOutcome
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * Records what happened at a renewal date and updates the policy to match: still in force,
     * lapsed, or cancelled. A renewed custom-date policy moves on to its next renewal date.
     */
    public function handle(Policy $policy, RenewalData $data, User $actor): PolicyRenewal
    {
        $awaiting = $policy->renewalAwaitingOutcome(CarbonImmutable::now($policy->user->timezone)->startOfDay());

        if ($awaiting?->toDateString() !== $data->dueOn->toDateString()) {
            throw ValidationException::withMessages(['due_on' => 'This renewal isn’t waiting for an outcome.']);
        }

        return DB::transaction(function () use ($policy, $data, $actor) {
            $renewal = $policy->renewals()->create([
                'user_id' => $actor->id,
                'due_on' => $data->dueOn,
                'outcome' => $data->outcome,
                'notes' => $data->notes,
            ]);

            $policy->update([
                'status' => $data->outcome->policyStatus(),
                'renewal_date' => $data->outcome === RenewalOutcome::Renewed && $policy->renewal_term === RenewalTerm::Custom
                    ? $data->nextRenewalDate
                    : $policy->renewal_date,
            ]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::RenewalRecorded, $actor, [
                'due_on' => $data->dueOn->toDateString(),
                'outcome' => $data->outcome->value,
                'next_renewal_date' => $data->nextRenewalDate?->toDateString(),
            ]);

            return $renewal;
        });
    }
}
