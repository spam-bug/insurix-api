<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\RestoreWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\RestoreWorkspaceRequest;
use App\Http\Resources\Api\V1\WorkspaceResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;

class WorkspaceRestorationController extends Controller
{
    public function store(RestoreWorkspaceRequest $request, Workspace $workspace, #[CurrentUser] User $user, RestoreWorkspace $restoreWorkspace): WorkspaceResource
    {
        $restoreWorkspace->handle($workspace);

        return WorkspaceResource::make($user->workspaces()->findOrFail($workspace->id));
    }
}
