<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class CurrentUserController extends Controller
{
    public function show(#[CurrentUser] User $user): UserResource
    {
        return UserResource::make($user);
    }
}
