<?php

declare(strict_types=1);

namespace App\Actions\Payments;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\PolicyPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeletePolicyPayment
{
    public function __construct(
        private AnnounceWorkspaceChange $announceWorkspaceChange,
    ) {}

    /**
     * Removes a premium recorded by mistake, so it shows as due again. Only the latest one can
     * go: payments are kept in order, and the next due date is counted from the last.
     */
    public function handle(PolicyPayment $payment): void
    {
        $policy = $payment->policy;

        if ($policy->payments()->where('due_on', '>', $payment->due_on)->exists()) {
            throw ValidationException::withMessages(['payment' => 'Remove the later payments first.']);
        }

        DB::transaction(fn () => $payment->delete());

        $this->announceWorkspaceChange->handle($policy->workspace_id, new WorkspaceChangeData(ChangeTopic::Policies, ChangeAction::Updated, $policy->id, $policy->product_name));
    }
}
