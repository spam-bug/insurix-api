<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\Actions\Policies\RecordPolicyEvent;
use App\DataTransferObject\Payments\PolicyPaymentData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\PolicyEventType;
use App\Models\PolicyPayment;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class UpdatePolicyPayment
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
        private EstimatePaymentCommission $estimatePaymentCommission,
        private RecordPolicyEvent $recordPolicyEvent,
    ) {}

    /**
     * Corrects a recorded premium, or records the commission the agent actually received for
     * it (from their statement). Without a date, commission counts as received today; clearing
     * the amount marks it as not received again.
     */
    public function handle(PolicyPayment $payment, PolicyPaymentData $data, ?User $actor = null): PolicyPayment
    {
        $policy = $payment->policy;
        $amount = (float) ($data->amount ?? $payment->amount);
        $firstReceipt = $payment->commission_received === null && $data->commissionReceived !== null;

        DB::transaction(function () use ($payment, $policy, $data, $actor, $amount, $firstReceipt) {
            $payment->update([
                'paid_on' => $data->paidOn,
                'amount' => $amount,
                'commission_expected' => $this->estimatePaymentCommission->handle($policy, $payment->due_on, $amount),
                'commission_received' => $data->commissionReceived,
                'commission_received_on' => $data->commissionReceived === null
                    ? null
                    : $data->commissionReceivedOn ?? CarbonImmutable::today($policy->workspace->timezone),
                'notes' => $data->notes,
            ]);

            if ($firstReceipt) {
                $this->recordPolicyEvent->handle($policy, PolicyEventType::CommissionReceived, $actor, [
                    'due_on' => $payment->due_on->toDateString(),
                    'amount' => (float) $data->commissionReceived,
                ]);
            }
        });

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $policy->id, $policy->product_name));

        return $payment;
    }
}
