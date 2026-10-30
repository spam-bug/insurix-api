<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Workspaces\CreateWorkspace;
use App\DataTransferObject\Auth\RegisterUserData;
use App\DataTransferObject\Workspaces\WorkspaceData;
use App\Enums\Feature;
use App\Enums\WorkspaceType;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature as Pennant;

class RegisterUser
{
    public function __construct(private CreateWorkspace $createWorkspace) {}

    /**
     * Creates the person's account. While onboarding is switched off, they also get an insurance
     * workspace right away, unless an invite is waiting to add them to someone else's.
     */
    public function handle(RegisterUserData $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data->toArray());

            if (Pennant::for($user)->inactive(Feature::WorkspaceOnboarding) && ! $this->hasPendingInvitation($user)) {
                $this->createWorkspace->handle($user, new WorkspaceData(
                    name: "{$user->name}'s workspace",
                    type: WorkspaceType::Insurance,
                    timezone: Workspace::DEFAULT_TIMEZONE,
                ));
            }

            return $user;
        });

        Auth::guard('web')->login($user);
        session()->regenerate();

        return $user;
    }

    private function hasPendingInvitation(User $user): bool
    {
        return WorkspaceInvitation::query()
            ->where('email', $user->email)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->exists();
    }
}
