<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\RemoveMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\ManageWorkspaceRequest;
use App\Http\Resources\Api\V1\WorkspaceMemberResource;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorkspaceMemberController extends Controller
{
    public function index(Workspace $workspace): AnonymousResourceCollection
    {
        return WorkspaceMemberResource::collection($workspace->members()->with('user')->oldest()->get());
    }

    public function destroy(ManageWorkspaceRequest $request, WorkspaceMember $member, RemoveMember $removeMember): Response
    {
        $removeMember->handle($member);

        return response()->noContent();
    }
}
