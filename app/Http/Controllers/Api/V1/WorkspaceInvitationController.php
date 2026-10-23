<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\InviteMember;
use App\Actions\Workspaces\RevokeInvitation;
use App\DataTransferObject\Workspaces\InvitationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\ManageWorkspaceRequest;
use App\Http\Requests\Api\V1\Workspaces\StoreInvitationRequest;
use App\Http\Resources\Api\V1\WorkspaceInvitationResource;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class WorkspaceInvitationController extends Controller
{
    /** Invites still waiting to be accepted. */
    public function index(ManageWorkspaceRequest $request, Workspace $workspace): AnonymousResourceCollection
    {
        return WorkspaceInvitationResource::collection(
            $workspace->invitations()->with('inviter:id,name')->whereNull('accepted_at')->latest()->get(),
        );
    }

    /** The accept link comes back once, next to the invite, so the owner can also share it themselves. */
    public function store(StoreInvitationRequest $request, Workspace $workspace, #[CurrentUser] User $user, InviteMember $inviteMember): JsonResponse
    {
        ['invitation' => $invitation, 'token' => $token] = $inviteMember->handle($workspace, InvitationData::fromArray($request->validated()), $user);

        return WorkspaceInvitationResource::make($invitation->load('inviter:id,name'))
            ->additional(['accept_url' => config('app.frontend_url')."/invitations/{$token}"])
            ->response()
            ->setStatusCode(201);
    }

    public function destroy(ManageWorkspaceRequest $request, WorkspaceInvitation $invitation, RevokeInvitation $revokeInvitation): Response
    {
        $revokeInvitation->handle($invitation);

        return response()->noContent();
    }
}
