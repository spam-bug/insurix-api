<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\CreateFollowUp;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FollowUps\StoreFollowUpRequest;
use App\Http\Resources\Api\V1\FollowUpResource;
use App\Models\Client;
use Illuminate\Http\JsonResponse;

class ClientFollowUpController extends Controller
{
    public function store(StoreFollowUpRequest $request, Client $client, CreateFollowUp $createFollowUp): JsonResponse
    {
        return FollowUpResource::make($createFollowUp->handle($client, FollowUpData::fromArray($request->validated()))->load('lead', 'client'))
            ->response()
            ->setStatusCode(201);
    }
}
