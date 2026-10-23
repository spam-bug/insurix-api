<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\UpdateProfile;
use App\DataTransferObject\Account\ProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateProfileRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

/** The agent's name, contact details and timezone. */
class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, #[CurrentUser] User $agent, UpdateProfile $updateProfile): UserResource
    {
        return UserResource::make($updateProfile->handle($agent, ProfileData::fromArray($request->validated())));
    }
}
