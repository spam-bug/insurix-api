<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\DataTransferObjects\Account\ProfileData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UpdateProfile
{
    public function handle(User $agent, ProfileData $data): User
    {
        DB::transaction(fn () => $agent->update($data->toAttributes()));

        return $agent;
    }
}
