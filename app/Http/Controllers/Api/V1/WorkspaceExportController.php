<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\ListWorkspaceExports;
use App\Actions\Workspaces\StartWorkspaceExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\ManageWorkspaceRequest;
use App\Http\Resources\Api\V1\WorkspaceExportResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/** Exports hold every client's details, so only the owner asks for and sees them. */
class WorkspaceExportController extends Controller
{
    public function index(ManageWorkspaceRequest $request, Workspace $workspace, ListWorkspaceExports $listWorkspaceExports): AnonymousResourceCollection
    {
        return WorkspaceExportResource::collection($listWorkspaceExports->handle($workspace));
    }

    public function store(ManageWorkspaceRequest $request, Workspace $workspace, #[CurrentUser] User $user, StartWorkspaceExport $startWorkspaceExport): JsonResponse
    {
        return WorkspaceExportResource::make($startWorkspaceExport->handle($workspace, $user)->load('requester:id,name'))
            ->response()
            ->setStatusCode(202);
    }
}
