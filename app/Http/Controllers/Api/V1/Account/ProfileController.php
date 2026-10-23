<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ReplaceAccountImage;
use App\Actions\Account\UpdateProfile;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateProfileRequest;
use App\Http\Requests\Api\V1\Account\UploadImageRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

/** The agent's name, contact details, timezone and profile photo. */
class ProfileController extends Controller
{
    public function update(UpdateProfileRequest $request, #[CurrentUser] User $agent, UpdateProfile $updateProfile): UserResource
    {
        return UserResource::make($updateProfile->handle($agent, $request->toData()));
    }

    public function storePhoto(UploadImageRequest $request, #[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, 'avatar', $request->file('image')));
    }

    public function destroyPhoto(#[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, 'avatar', null));
    }
}
