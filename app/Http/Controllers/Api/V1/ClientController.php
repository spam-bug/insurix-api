<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Clients\ListClientHistory;
use App\Actions\Clients\ListClients;
use App\Actions\Clients\UpdateClient;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Clients\ListClientsRequest;
use App\Http\Requests\Api\V1\Clients\UpdateClientRequest;
use App\Http\Resources\Api\V1\ClientResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

/** Clients are created by promoting a lead, so there is no store endpoint here. */
class ClientController extends Controller
{
    public function index(ListClientsRequest $request, #[CurrentUser] User $agent, ListClients $listClients): AnonymousResourceCollection
    {
        return ClientResource::collection($listClients->handle($agent, $request->toData()));
    }

    public function show(Client $client): ClientResource
    {
        Gate::authorize('view', $client);

        return ClientResource::make($client);
    }

    /**
     * The client's notes, meetings, policy changes and documents as one timeline, newest first.
     */
    public function history(Client $client, ListClientHistory $listClientHistory): JsonResponse
    {
        Gate::authorize('view', $client);

        return response()->json(['data' => $listClientHistory->handle($client)]);
    }

    public function update(UpdateClientRequest $request, Client $client, UpdateClient $updateClient): ClientResource
    {
        Gate::authorize('update', $client);

        return ClientResource::make($updateClient->handle($client, $request->toData()));
    }
}
