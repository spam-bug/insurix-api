<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\UpdatePassword;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdatePasswordRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

class PasswordController extends Controller
{
    /**
     * Changes the password and signs out every other device.
     */
    public function update(UpdatePasswordRequest $request, #[CurrentUser] User $agent, UpdatePassword $updatePassword): Response
    {
        $updatePassword->handle($agent, $request->validated('password'));

        return response()->noContent();
    }
}
