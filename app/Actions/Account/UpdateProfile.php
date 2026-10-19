<?php

namespace App\Actions\Account;

use App\DataTransferObjects\Account\ProfileData;
use App\Models\User;

class UpdateProfile
{
    public function handle(User $agent, ProfileData $data): User
    {
        $agent->update($data->toAttributes());

        return $agent;
    }
}
