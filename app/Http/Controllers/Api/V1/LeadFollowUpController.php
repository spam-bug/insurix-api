<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\CreateFollowUp;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FollowUps\StoreFollowUpRequest;
use App\Http\Resources\Api\V1\FollowUpResource;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;

class LeadFollowUpController extends Controller
{
    public function store(StoreFollowUpRequest $request, Lead $lead, CreateFollowUp $createFollowUp): JsonResponse
    {
        return FollowUpResource::make($createFollowUp->handle($lead, FollowUpData::fromArray($request->validated()))->load('lead'))
            ->response()
            ->setStatusCode(201);
    }
}
