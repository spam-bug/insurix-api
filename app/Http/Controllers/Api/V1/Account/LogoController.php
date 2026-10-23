<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ReplaceAccountImage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UploadImageRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

/** The logo at the top of the agent's shareable form. */
class LogoController extends Controller
{
    public function store(UploadImageRequest $request, #[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, 'logo', $request->file('image')));
    }

    public function destroy(#[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, 'logo', null));
    }
}
