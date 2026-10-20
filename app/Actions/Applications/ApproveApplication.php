<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Actions\Policies\RecordPolicyEvent;
use App\Enums\PolicyEventType;
use App\Enums\PolicyStatus;
use App\Models\Policy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class ApproveApplication
{
    public function __construct(private RecordPolicyEvent $recordPolicyEvent) {}

    /**
     * The insurer approved the application: the policy goes in force, with the effective date
     * and policy number from the approval when given.
     */
    public function handle(Policy $policy, ?CarbonImmutable $effectiveDate, ?string $policyNumber, ?User $actor): Policy
    {
        return DB::transaction(function () use ($policy, $effectiveDate, $policyNumber, $actor) {
            $policy->update([
                'status' => PolicyStatus::InForce,
                'application_stage' => null,
                'stage_changed_at' => null,
                'effective_date' => $effectiveDate ?? $policy->effective_date,
                'policy_number' => $policyNumber ?? $policy->policy_number,
            ]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::Approved, $actor, [
                'effective_date' => $policy->effective_date?->toDateString(),
                'policy_number' => $policy->policy_number,
            ]);

            return $policy;
        });
    }
}
