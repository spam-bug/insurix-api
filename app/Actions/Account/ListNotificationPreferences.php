<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\NotificationChannel;
use App\Enums\NotificationType;
use App\Models\User;

class ListNotificationPreferences
{
    /**
     * Every kind with the user's choice per channel, or the default when they haven't chosen.
     *
     * @return list<array{type: string, in_app: bool, email: bool}>
     */
    public function handle(User $user): array
    {
        return array_map(fn (NotificationType $type) => [
            'type' => $type->value,
            'in_app' => $type->isOnFor($user, NotificationChannel::InApp),
            'email' => $type->isOnFor($user, NotificationChannel::Email),
        ], NotificationType::cases());
    }
}
