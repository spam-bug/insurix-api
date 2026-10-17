<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ReplaceAccountImage;
use App\Actions\Account\UpdateLeadFormBio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateBioRequest;
use App\Http\Requests\Api\V1\Account\UploadImageRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

/** What prospective clients see at the top of the agent's shareable form: a bio and a logo. */
class LeadFormBrandingController extends Controller
{
    public function updateBio(UpdateBioRequest $request, #[CurrentUser] User $agent, UpdateLeadFormBio $updateLeadFormBio): UserResource
    {
        return UserResource::make($updateLeadFormBio->handle($agent, $request->bio()));
    }

    public function storeLogo(UploadImageRequest $request, #[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, 'logo', $request->file('image')));
    }

    public function destroyLogo(#[CurrentUser] User $agent, ReplaceAccountImage $replaceAccountImage): UserResource
    {
        return UserResource::make($replaceAccountImage->handle($agent, 'logo', null));
    }
}
