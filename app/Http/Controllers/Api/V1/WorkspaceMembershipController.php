<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\LeaveWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\LeaveWorkspaceRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Response;

/** The signed-in user's own place in the workspace. */
class WorkspaceMembershipController extends Controller
{
    public function destroy(LeaveWorkspaceRequest $request, Workspace $workspace, #[CurrentUser] User $user, LeaveWorkspace $leaveWorkspace): Response
    {
        $leaveWorkspace->handle($workspace, $user);

        return response()->noContent();
    }
}
