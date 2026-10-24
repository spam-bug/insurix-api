<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPolicy
{
    /** A notification is only ever its recipient's to read. */
    public function update(User $user, DatabaseNotification $notification): bool
    {
        return $notification->notifiable_type === $user->getMorphClass() && $notification->notifiable_id === $user->id;
    }
}
