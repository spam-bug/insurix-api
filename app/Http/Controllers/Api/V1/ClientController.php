<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clients\CreateClient;
use App\Actions\Clients\ListClients;
use App\Actions\Clients\UpdateClient;
use App\DataTransferObject\Clients\ClientData;
use App\DataTransferObject\Clients\ClientFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Clients\ListClientsRequest;
use App\Http\Requests\Api\V1\Clients\StoreClientRequest;
use App\Http\Requests\Api\V1\Clients\UpdateClientRequest;
use App\Http\Resources\Api\V1\ClientResource;
use App\Models\Client;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ClientController extends Controller
{
    public function index(ListClientsRequest $request, Workspace $workspace, ListClients $listClients): AnonymousResourceCollection
    {
        return ClientResource::collection($listClients->handle($workspace, ClientFiltersData::fromArray($request->validated())));
    }

    public function store(StoreClientRequest $request, Workspace $workspace, CreateClient $createClient): JsonResponse
    {
        return ClientResource::make($createClient->handle($workspace, ClientData::fromArray($request->validated())))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Client $client): ClientResource
    {
        return ClientResource::make($client);
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClient $updateClient): ClientResource
    {
        return ClientResource::make($updateClient->handle($client, ClientData::fromArray($request->validated())));
    }
}
