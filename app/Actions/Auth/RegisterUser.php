<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObject\Auth\RegisterUserData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    /**
     * Creates the agent's account and signs them in.
     */
    public function handle(RegisterUserData $data): User
    {
        $user = DB::transaction(fn () => User::create($data->toArray()));

        Auth::guard('web')->login($user);
        session()->regenerate();

        return $user;
    }
}
