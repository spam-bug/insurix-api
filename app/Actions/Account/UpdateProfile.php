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
        DB::transaction(fn () => $agent->update($data->toArray()));

        Cache::tags("agent:{$agent->id}")->flush();

        return $agent;
    }
}
