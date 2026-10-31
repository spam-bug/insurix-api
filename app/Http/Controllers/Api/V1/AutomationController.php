<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Automations\CreateAutomation;
use App\Actions\Automations\DeleteAutomation;
use App\Actions\Automations\ListAutomations;
use App\Actions\Automations\UpdateAutomation;
use App\DataTransferObject\Automations\AutomationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Automations\ListAutomationsRequest;
use App\Http\Requests\Api\V1\Automations\StoreAutomationRequest;
use App\Http\Requests\Api\V1\Automations\UpdateAutomationRequest;
use App\Http\Resources\Api\V1\AutomationResource;
use App\Models\Automation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class AutomationController extends Controller
{
    public function index(ListAutomationsRequest $request, Workspace $workspace, ListAutomations $listAutomations): AnonymousResourceCollection
    {
        return AutomationResource::collection($listAutomations->handle($workspace));
    }

    public function store(StoreAutomationRequest $request, Workspace $workspace, #[CurrentUser] User $user, CreateAutomation $createAutomation): JsonResponse
    {
        return AutomationResource::make($createAutomation->handle($workspace, AutomationData::fromArray($request->validated()), $user))
            ->response()
            ->setStatusCode(201);
    }

    public function update(UpdateAutomationRequest $request, Automation $automation, UpdateAutomation $updateAutomation): AutomationResource
    {
        return AutomationResource::make($updateAutomation->handle($automation, AutomationData::fromArray($request->validated())));
    }

    public function destroy(Automation $automation, DeleteAutomation $deleteAutomation): Response
    {
        $deleteAutomation->handle($automation);

        return response()->noContent();
    }
}
