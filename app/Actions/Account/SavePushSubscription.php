<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\DataTransferObject\Account\PushSubscriptionData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class SavePushSubscription
{
    /** Saves this device for push notifications. A device that belonged to someone else moves to this user. */
    public function handle(User $user, PushSubscriptionData $data): void
    {
        DB::transaction(fn () => $user->updatePushSubscription($data->endpoint, $data->publicKey, $data->authToken, $data->contentEncoding));
    }
}
