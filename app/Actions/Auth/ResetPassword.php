<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObject\Auth\PasswordResetData;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ResetPassword
{
    /**
     * Sets the new password from a reset link and signs the agent out of every device, since
     * whoever had the old password may still be signed in.
     *
     * @throws ValidationException
     */
    public function handle(PasswordResetData $data): void
    {
        $status = Password::reset(
            ['email' => Str::lower(trim($data->email)), 'token' => $data->token, 'password' => $data->password],
            fn (User $user, string $password) => DB::transaction(function () use ($user, $password) {
                $user->forceFill(['password' => $password])->save();
                $user->tokens()->delete();
            }),
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
    }
}
