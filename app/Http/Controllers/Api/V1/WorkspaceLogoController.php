<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\ReplaceWorkspaceLogo;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\ManageWorkspaceRequest;
use App\Http\Requests\Api\V1\Workspaces\UploadWorkspaceLogoRequest;
use App\Http\Resources\Api\V1\WorkspaceResource;
use App\Models\Workspace;

class WorkspaceLogoController extends Controller
{
    public function store(UploadWorkspaceLogoRequest $request, Workspace $workspace, ReplaceWorkspaceLogo $replaceWorkspaceLogo): WorkspaceResource
    {
        return WorkspaceResource::make($replaceWorkspaceLogo->handle($workspace, $request->file('image')));
    }

    public function destroy(ManageWorkspaceRequest $request, Workspace $workspace, ReplaceWorkspaceLogo $replaceWorkspaceLogo): WorkspaceResource
    {
        return WorkspaceResource::make($replaceWorkspaceLogo->handle($workspace, null));
    }
}
