<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\CreateWorkspace;
use App\Actions\Workspaces\DeleteWorkspace;
use App\Actions\Workspaces\ListWorkspaces;
use App\Actions\Workspaces\UpdateWorkspace;
use App\DataTransferObject\Workspaces\WorkspaceData;
use App\DataTransferObject\Workspaces\WorkspaceSettingsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\DeleteWorkspaceRequest;
use App\Http\Requests\Api\V1\Workspaces\StoreWorkspaceRequest;
use App\Http\Requests\Api\V1\Workspaces\UpdateWorkspaceRequest;
use App\Http\Resources\Api\V1\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorkspaceController extends Controller
{
    public function index(#[CurrentUser] User $user, ListWorkspaces $listWorkspaces): AnonymousResourceCollection
    {
        return WorkspaceResource::collection($listWorkspaces->handle($user));
    }

    public function store(StoreWorkspaceRequest $request, #[CurrentUser] User $user, CreateWorkspace $createWorkspace): JsonResponse
    {
        $workspace = $createWorkspace->handle($user, WorkspaceData::fromArray($request->validated()));

        return WorkspaceResource::make($user->workspaces()->findOrFail($workspace->id))->response()->setStatusCode(201);
    }

    public function show(Workspace $workspace, #[CurrentUser] User $user): WorkspaceResource
    {
        return WorkspaceResource::make($user->workspaces()->findOrFail($workspace->id));
    }

    public function update(UpdateWorkspaceRequest $request, Workspace $workspace, #[CurrentUser] User $user, UpdateWorkspace $updateWorkspace): WorkspaceResource
    {
        $updateWorkspace->handle($workspace, WorkspaceSettingsData::fromArray($request->validated()));

        return WorkspaceResource::make($user->workspaces()->findOrFail($workspace->id));
    }

    public function destroy(DeleteWorkspaceRequest $request, Workspace $workspace, #[CurrentUser] User $user, DeleteWorkspace $deleteWorkspace): Response
    {
        $deleteWorkspace->handle($workspace, $user);

        return response()->noContent();
    }
}
