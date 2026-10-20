<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clients\PromoteLead;
use App\DataTransferObject\Clients\ClientProfileData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\PromoteLeadRequest;
use App\Http\Resources\Api\V1\ClientResource;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class LeadPromotionController extends Controller
{
    public function store(PromoteLeadRequest $request, Lead $lead, #[CurrentUser] User $agent, PromoteLead $promoteLead): JsonResponse
    {
        return ClientResource::make($promoteLead->handle($lead, ClientProfileData::fromArray($request->validated()), $agent))
            ->response()
            ->setStatusCode(201);
    }
}
