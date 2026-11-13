<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PolicyPayment;
use App\Models\User;
use App\Policies\Concerns\ChecksMembership;

class PolicyPaymentPolicy
{
    use ChecksMembership;

    public function update(User $user, PolicyPayment $payment): bool
    {
        return $this->isMember($user, $payment->workspace_id);
    }

    public function delete(User $user, PolicyPayment $payment): bool
    {
        return $this->isMember($user, $payment->workspace_id);
    }
}
