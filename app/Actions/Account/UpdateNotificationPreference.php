<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\DataTransferObject\Account\NotificationPreferenceData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateNotificationPreference
{
    /** Locks the row, so switches toggled quickly one after another don't overwrite each other. */
    public function handle(User $user, NotificationPreferenceData $data): User
    {
        return DB::transaction(function () use ($user, $data) {
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $preferences = $locked->notification_preferences ?? [];
            $preferences[$data->type->value] = $data->enabled;

            $locked->forceFill(['notification_preferences' => $preferences])->save();

            return $locked;
        });
    }
}
