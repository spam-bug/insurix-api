<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\Actions\Policies\FindNextPaymentDue;
use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Payments\PolicyPaymentData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\PolicyEventType;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecordPolicyPayment
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private FindNextPaymentDue $findNextPaymentDue,
        private EstimatePaymentCommission $estimatePaymentCommission,
        private RecordPolicyEvent $recordPolicyEvent,
    ) {}

    /**
     * Marks the policy's next unpaid premium as paid, for the usual premium unless another
     * amount is given, and works out the commission it should earn.
     */
    public function handle(Policy $policy, PolicyPaymentData $data, ?User $actor = null): PolicyPayment
    {
        $dueOn = $this->findNextPaymentDue->handle($policy);

        if ($dueOn === null) {
            throw ValidationException::withMessages(['paid_on' => 'This policy has no premium waiting to be paid.']);
        }

        $amount = (float) ($data->amount ?? $policy->premium);

        $payment = DB::transaction(function () use ($policy, $data, $actor, $dueOn, $amount) {
            $payment = $policy->payments()->create([
                'workspace_id' => $policy->workspace_id,
                'due_on' => $dueOn,
                'paid_on' => $data->paidOn,
                'amount' => $amount,
                'commission_expected' => $this->estimatePaymentCommission->handle($policy, $dueOn, $amount),
                'notes' => $data->notes,
            ]);

            $this->recordPolicyEvent->handle($policy, PolicyEventType::PremiumPaid, $actor, [
                'due_on' => $dueOn->toDateString(),
                'amount' => $amount,
            ]);

            return $payment;
        });

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $policy->id, $policy->product_name));

        return $payment;
    }
}
