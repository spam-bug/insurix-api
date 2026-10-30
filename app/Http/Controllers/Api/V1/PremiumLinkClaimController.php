<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Premium\ClaimPremiumLink;
use App\DataTransferObject\Premium\ClaimPremiumLinkData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Premium\ClaimPremiumLinkRequest;
use App\Http\Resources\Api\V1\WorkspaceResource;
use App\Models\PremiumLink;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class PremiumLinkClaimController extends Controller
{
    public function store(ClaimPremiumLinkRequest $request, PremiumLink $premiumLink, #[CurrentUser] User $user, ClaimPremiumLink $claimPremiumLink): JsonResponse
    {
        $workspace = $claimPremiumLink->handle($premiumLink, $user, ClaimPremiumLinkData::fromArray($request->validated()));

        return WorkspaceResource::make($user->workspaces()->findOrFail($workspace->id))->response()->setStatusCode(201);
    }
}
