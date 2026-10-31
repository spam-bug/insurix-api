<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\ServiceRequests\CreateServiceRequest;
use App\DataTransferObject\ServiceRequests\ServiceRequestData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ServiceRequests\StoreServiceRequestRequest;
use App\Http\Resources\Api\V1\ServiceRequestResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class ClientServiceRequestController extends Controller
{
    public function store(StoreServiceRequestRequest $request, Client $client, #[CurrentUser] User $user, CreateServiceRequest $createServiceRequest): JsonResponse
    {
        return ServiceRequestResource::make($createServiceRequest->handle($client, ServiceRequestData::fromArray($request->validated()), $user)->load(ServiceRequestResource::RELATIONS))
            ->response()
            ->setStatusCode(201);
    }
}
