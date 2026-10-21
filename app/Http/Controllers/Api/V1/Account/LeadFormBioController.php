<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\UpdateLeadFormBio;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateBioRequest;
use App\Http\Resources\Api\V1\UserResource;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

/** The bio prospective clients see at the top of the agent's shareable form. */
class LeadFormBioController extends Controller
{
    public function update(UpdateBioRequest $request, #[CurrentUser] User $agent, UpdateLeadFormBio $updateLeadFormBio): UserResource
    {
        return UserResource::make($updateLeadFormBio->handle($agent, $request->validated('bio')));
    }
}
