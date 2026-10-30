<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\DataTransferObject\Account\ProfileData;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UpdateProfile
{
    public function handle(User $agent, ProfileData $data): User
    {
        DB::transaction(function () use ($agent, $data) {
            $agent->fill($data->toArray());

            // A new address needs confirming again.
            if ($agent->isDirty('email')) {
                $agent->email_verified_at = null;
            }

            $agent->save();
        });

        if ($agent->wasChanged('email')) {
            $agent->sendEmailVerificationNotification();
        }

        // Names show on timelines in every workspace the person belongs to.
        $agent->workspaces()->pluck('workspaces.id')->each(fn (string $id) => Cache::tags("workspace:{$id}")->flush());

        return $agent;
    }
}
