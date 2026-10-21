<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Applications\ApprovalData;
use App\Enums\PolicyEventType;
use App\Enums\PolicyStatus;
use App\Enums\RenewalTerm;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApproveApplication
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * The insurer approved the application: the policy goes in force, with the effective date
     * and policy number from the approval when given.
     */
    public function handle(Policy $policy, ApprovalData $data, ?User $actor): Policy
    {
        if ($policy->status !== PolicyStatus::Pending) {
            throw ValidationException::withMessages(['status' => 'Only a pending application can change this way.']);
        }

        $needsEffectiveDate = $policy->renewal_term !== null && $policy->renewal_term !== RenewalTerm::Custom && $policy->effective_date === null;

        if ($needsEffectiveDate && $data->effectiveDate === null) {
            throw ValidationException::withMessages(['effective_date' => 'Add the date it takes effect, so its renewals can be worked out.']);
        }

        return DB::transaction(function () use ($policy, $data, $actor) {
            $policy->update([
                'status' => PolicyStatus::InForce,
                'application_stage' => null,
                'stage_changed_at' => null,
                'effective_date' => $data->effectiveDate ?? $policy->effective_date,
                'policy_number' => $data->policyNumber ?? $policy->policy_number,
            ]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::Approved, $actor, [
                'effective_date' => $policy->effective_date?->toDateString(),
                'policy_number' => $policy->policy_number,
            ]);

            return $policy;
        });
    }
}
