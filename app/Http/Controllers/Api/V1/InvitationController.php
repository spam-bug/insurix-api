<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\WorkspaceInvitationResource;
use App\Http\Resources\Api\V1\WorkspaceResource;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Container\Attributes\CurrentUser;

/** An invite opened from its link, by the person it was sent to. */
class InvitationController extends Controller
{
    public function show(WorkspaceInvitation $invitation): WorkspaceInvitationResource
    {
        return WorkspaceInvitationResource::make($invitation->load(['workspace', 'inviter:id,name']));
    }

    public function update(WorkspaceInvitation $invitation, #[CurrentUser] User $user, AcceptInvitation $acceptInvitation): WorkspaceResource
    {
        $workspace = $acceptInvitation->handle($invitation, $user);

        return WorkspaceResource::make($user->workspaces()->findOrFail($workspace->id));
    }
}
