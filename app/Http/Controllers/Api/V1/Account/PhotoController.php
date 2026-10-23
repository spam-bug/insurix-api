<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ReplaceAccountImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UploadImageRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

/** The agent's profile photo. */
class PhotoController extends Controller
{
    public function store(UploadImageRequest $request, #[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, $request->file('image')));
    }

    public function destroy(#[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, null));
    }
}
