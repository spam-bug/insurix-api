<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Policies\CreatePolicy;
use App\DataTransferObject\Policies\PolicyData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Policies\StorePolicyRequest;
use App\Http\Resources\Api\V1\PolicyResource;
use App\Models\Client;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class ClientPolicyController extends Controller
{
    public function store(StorePolicyRequest $request, Client $client, #[CurrentUser] User $user, CreatePolicy $createPolicy): JsonResponse
    {
        return PolicyResource::make($createPolicy->handle($client, PolicyData::fromArray($request->validated()), $user)->load('client:id,name,lead_id'))
            ->response()
            ->setStatusCode(201);
    }
}
