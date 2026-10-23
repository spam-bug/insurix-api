<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObject\Auth\RegisterUserData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    public function handle(RegisterUserData $data): User
    {
        return DB::transaction(fn () => User::create([
            'name' => $data->name,
            'email' => $data->email,
            'password' => $data->password,
        ]));
    }
}
