<?php

declare(strict_types=1);

namespace App\Actions\Premium;

use App\Actions\Workspaces\CreateOwnWorkspace;
use App\DataTransferObject\Premium\ClaimPremiumLinkData;
use App\DataTransferObject\Premium\PremiumGrantData;
use App\Enums\PremiumLinkState;
use App\Enums\WorkspaceRole;
use App\Models\PremiumLink;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ClaimPremiumLink
{
    public function __construct(
        private GrantPremium $grantPremium,
        private CreateOwnWorkspace $createOwnWorkspace,
    ) {}

    /**
     * Gives Premium to a workspace the user owns: the one they picked, or their only one. Someone
     * who owns none (only invited into others) gets their own workspace first. The email check
     * lives in the premium link policy; the link is locked so its last claim can't be taken twice.
     */
    public function handle(PremiumLink $link, User $user, ClaimPremiumLinkData $data): Workspace
    {
        return DB::transaction(function () use ($link, $user, $data) {
            $link = PremiumLink::query()->lockForUpdate()->withCount('grants')->findOrFail($link->id);

            if ($link->state() !== PremiumLinkState::Available) {
                throw ValidationException::withMessages(['link' => $link->state()->label()]);
            }

            $workspace = $this->workspaceFor($user, $data);

            if ($link->grants()->where('workspace_id', $workspace->id)->exists()) {
                throw ValidationException::withMessages(['link' => 'This workspace has already claimed this link.']);
            }

            $this->grantPremium->handle($workspace, new PremiumGrantData(days: $link->days), $user, $link);

            return $workspace;
        });
    }

    private function workspaceFor(User $user, ClaimPremiumLinkData $data): Workspace
    {
        $owned = $user->workspaces()->wherePivot('role', WorkspaceRole::Owner->value)->get(['workspaces.id']);

        if ($data->workspaceId !== null) {
            return $owned->firstOrFail(fn (Workspace $workspace) => $workspace->id === $data->workspaceId);
        }
        if ($owned->count() > 1) {
            throw ValidationException::withMessages(['workspace_id' => 'Pick the workspace to give Premium to.']);
        }

        return $owned->first() ?? $this->createOwnWorkspace->handle($user);
    }
}
