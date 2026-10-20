<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Actions\Auth\RevokeCurrentAccessToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

class LogoutController extends Controller
{
    public function __invoke(#[CurrentUser] User $user, RevokeCurrentAccessToken $revokeCurrentAccessToken): Response
    {
        $revokeCurrentAccessToken->handle($user);

        return response()->noContent();
    }
}
