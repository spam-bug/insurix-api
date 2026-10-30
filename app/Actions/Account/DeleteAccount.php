<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Actions\Auth\LogOut;
use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\Actions\Workspaces\PurgeWorkspace;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\WorkspaceDeletedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

class DeleteAccount
{
    public function __construct(
        private PurgeWorkspace $purgeWorkspace,
        private LogOut $logOut,
        private AnnounceWorkspaceChange $announceWorkspaceChange,
    ) {}

    /**
     * Deletes the agent and every workspace they own, at once and for good, even ones teammates
     * still use: those teammates get an email (there's no owner left to restore it). In
     * workspaces they help in, they simply drop out of the team.
     */
    public function handle(User $agent): void
    {
        $owned = Workspace::withTrashed()
            ->whereHas('members', fn ($query) => $query->where('user_id', $agent->id)->where('role', WorkspaceRole::Owner))
            ->with('users')
            ->get();
        $helping = $agent->workspaces()->wherePivot('role', WorkspaceRole::Member)->pluck('workspaces.id');

        foreach ($owned as $workspace) {
            // Members of an already deleted workspace heard about it then.
            if (! $workspace->trashed()) {
                Notification::send(
                    $workspace->users->reject(fn (User $user) => $user->is($agent)),
                    new WorkspaceDeletedNotification($workspace->name, $agent->name, null),
                );
            }

            $this->purgeWorkspace->handle($workspace);
        }

        // Sign out first: logging out writes to the user, which would bring a deleted row back.
        $this->logOut->handle();

        DB::transaction(function () use ($agent) {
            DB::table('sessions')->where('user_id', $agent->id)->delete();
            $agent->delete();
        });

        if ($agent->avatar_path) {
            Storage::disk(User::UPLOADS_DISK)->delete($agent->avatar_path);
        }

        $helping->each(fn (string $workspaceId) => $this->announceWorkspaceChange->handle(
            $workspaceId,
            new WorkspaceChangeData(ChangeTopic::Team, ChangeAction::Deleted, $agent->id),
        ));
    }
}
