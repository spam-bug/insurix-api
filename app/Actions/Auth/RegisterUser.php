<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Actions\Workspaces\CreateWorkspace;
use App\DataTransferObject\Auth\RegisterUserData;
use App\DataTransferObject\Workspaces\WorkspaceData;
use App\Enums\WorkspaceType;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisterUser
{
    public function __construct(private CreateWorkspace $createWorkspace) {}

    /**
     * Creates the person's account with a first insurance workspace they own, and signs them
     * in. Onboarding will let them pick the template; until then every workspace is insurance.
     */
    public function handle(RegisterUserData $data): User
    {
        $user = DB::transaction(function () use ($data) {
            $user = User::create($data->toArray());
            $this->createWorkspace->handle($user, new WorkspaceData(name: "{$user->name}’s workspace", type: WorkspaceType::Insurance));

            return $user;
        });

        Auth::guard('web')->login($user);
        session()->regenerate();

        return $user;
    }
}
