<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\DataTransferObject\Workspaces\InvitationData;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InviteMember
{
    /** How long an invite link works. */
    public const VALID_DAYS = 7;

    /**
     * Invites an email address to the workspace and emails them the link (queued). Inviting the
     * same address again replaces their pending invite with a fresh link. The plain token is
     * returned so the owner can also copy the link.
     *
     * @return array{invitation: WorkspaceInvitation, token: string}
     */
    public function handle(Workspace $workspace, InvitationData $data, User $inviter): array
    {
        $email = Str::lower($data->email);

        if ($workspace->users()->where('email', $email)->exists()) {
            throw ValidationException::withMessages(['email' => 'They’re already in this workspace.']);
        }

        $token = Str::random(64);

        $invitation = DB::transaction(function () use ($workspace, $data, $inviter, $email, $token) {
            $workspace->invitations()->where('email', $email)->whereNull('accepted_at')->delete();

            return $workspace->invitations()->create([
                'email' => $email,
                'role' => $data->role,
                'token' => $token,
                'invited_by' => $inviter->id,
                'expires_at' => now()->addDays(self::VALID_DAYS),
            ]);
        });

        DB::afterCommit(fn () => Notification::route('mail', $email)->notify(new WorkspaceInvitationNotification($invitation, $token)));

        return ['invitation' => $invitation, 'token' => $token];
    }
}
