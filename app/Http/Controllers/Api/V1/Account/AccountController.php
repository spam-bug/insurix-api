<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\DeleteAccount;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\DeleteAccountRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

class AccountController extends Controller
{
    public function destroy(DeleteAccountRequest $request, #[CurrentUser] User $user, DeleteAccount $deleteAccount): Response
    {
        $deleteAccount->handle($user);

        return response()->noContent();
    }
}
