<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Workspaces\CreateOwnWorkspace;
use App\DataTransferObject\Auth\RegisterUserData;
use App\Enums\Feature;
use App\Models\User;
use App\Models\WorkspaceInvitation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Pennant\Feature as Pennant;

class RegisterUser
{
    public function __construct(private CreateOwnWorkspace $createOwnWorkspace) {}

    /**
     * Creates the person's account. While onboarding is switched off, they also get an insurance
     * workspace right away, unless an invite is waiting to add them to someone else's. A link to
     * confirm their email goes out too.
     */
    public function handle(RegisterUserData $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data->toArray());

            if (Pennant::for($user)->inactive(Feature::WorkspaceOnboarding) && ! $this->hasPendingInvitation($user)) {
                $this->createOwnWorkspace->handle($user);
            }

            return $user;
        });

        $user->sendEmailVerificationNotification();

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
