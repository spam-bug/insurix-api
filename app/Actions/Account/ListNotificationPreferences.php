<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Enums\NotificationType;
use App\Models\User;

class ListNotificationPreferences
{
    /**
     * Every kind, and whether the user gets it (in the bell and by email).
     *
     * @return list<array{type: string, enabled: bool}>
     */
    public function handle(User $user): array
    {
        return array_map(fn (NotificationType $type) => [
            'type' => $type->value,
            'enabled' => $type->isOnFor($user),
        ], NotificationType::cases());
    }
}
