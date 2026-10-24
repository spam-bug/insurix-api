<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\MemberJoinedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class AcceptInvitation
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Adds the signed-in user to the workspace with the invited role. The email check lives in
     * the invitation policy; here an invite works once and only until it expires. The owner
     * hears about it through the bell.
     */
    public function handle(WorkspaceInvitation $invitation, User $user): Workspace
    {
        if ($invitation->accepted_at !== null) {
            throw ValidationException::withMessages(['invitation' => 'This invite has already been used.']);
        }
        if ($invitation->expires_at->isPast()) {
            throw ValidationException::withMessages(['invitation' => 'This invite has expired. Ask for a new one.']);
        }

        $workspace = $invitation->workspace;

        DB::transaction(function () use ($invitation, $user, $workspace) {
            $workspace->users()->syncWithoutDetaching([$user->id => ['role' => $invitation->role]]);
            $invitation->update(['accepted_at' => now()]);
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Team, ChangeAction::Created, $user->id, $user->name));

        Notification::send(
            $workspace->users()->wherePivot('role', WorkspaceRole::Owner->value)->get(),
            new MemberJoinedNotification($workspace, $user),
        );

        return $workspace;
    }
}
