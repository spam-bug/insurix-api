<?php

declare(strict_types=1);

namespace App\Actions\Renewals;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\Actions\Payments\RecordPolicyPayment;
use App\Actions\Policies\FindNextPaymentDue;
use App\Actions\Policies\FindRenewalAwaitingOutcome;
use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Payments\PolicyPaymentData;
use App\DataTransferObject\Renewals\RenewalData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
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
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private RecordPolicyEvent $recordPolicyEvent,
        private FindRenewalAwaitingOutcome $findRenewalAwaitingOutcome,
        private FindNextPaymentDue $findNextPaymentDue,
        private RecordPolicyPayment $recordPolicyPayment,
    ) {}

    /**
     * Records what happened at a renewal date and updates the policy to match: still in force,
     * lapsed, or cancelled. A renewed custom-date policy moves on to its next renewal date. When the
     * premium due on the renewal date is the one waiting, renewing counts as paying it, so the
     * agent doesn't record the same thing twice.
     */
    public function handle(Policy $policy, RenewalData $data, User $actor): PolicyRenewal
    {
        $awaiting = $this->findRenewalAwaitingOutcome->handle($policy, CarbonImmutable::now($policy->workspace->timezone)->startOfDay());

        if ($awaiting?->toDateString() !== $data->dueOn->toDateString()) {
            throw ValidationException::withMessages(['due_on' => 'This renewal isn’t waiting for an outcome.']);
        }

        $renewalPaysPremium = $data->outcome === RenewalOutcome::Renewed
            && $this->findNextPaymentDue->handle($policy)?->toDateString() === $data->dueOn->toDateString();

        $renewal = DB::transaction(function () use ($policy, $data, $actor, $renewalPaysPremium) {
            $renewal = $policy->renewals()->create([
                'workspace_id' => $policy->workspace_id,
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

            if ($renewalPaysPremium) {
                $this->recordPolicyPayment->handle($policy, new PolicyPaymentData(paidOn: CarbonImmutable::today($policy->workspace->timezone)), $actor);
            }

            return $renewal;
        });

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $policy->id, $policy->product_name));

        return $renewal;
    }
}
