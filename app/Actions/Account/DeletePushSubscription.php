<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class DeletePushSubscription
{
    /** Stops push notifications to this device, on turning them off or logging out. */
    public function handle(User $user, string $endpoint): void
    {
        DB::transaction(fn () => $user->deletePushSubscription($endpoint));
    }
}
