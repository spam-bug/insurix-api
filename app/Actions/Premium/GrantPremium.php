<?php

declare(strict_types=1);

namespace App\Actions\Premium;

use App\DataTransferObject\Premium\PremiumGrantData;
use App\Enums\Plan;
use App\Models\PremiumGrant;
use App\Models\PremiumLink;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class GrantPremium
{
    /**
     * Gives the workspace Premium for the given days, or forever without days. Days stack on top of
     * Premium that is still running; a forever grant stays forever. Each grant is kept as history.
     */
    public function handle(Workspace $workspace, PremiumGrantData $data, ?User $user = null, ?PremiumLink $link = null): PremiumGrant
    {
        return DB::transaction(function () use ($workspace, $data, $user, $link) {
            $workspace = Workspace::query()->lockForUpdate()->findOrFail($workspace->id);
            $foreverAlready = $workspace->currentPlan() === Plan::Premium && $workspace->plan_ends_at === null;

            $endsAt = match (true) {
                $data->days === null, $foreverAlready => null,
                $workspace->currentPlan() === Plan::Premium => $workspace->plan_ends_at->addDays($data->days),
                default => now()->addDays($data->days),
            };

            $workspace->forceFill(['plan' => Plan::Premium, 'plan_ends_at' => $endsAt])->save();

            return $workspace->premiumGrants()->create([
                'premium_link_id' => $link?->id,
                'user_id' => $user?->id,
                'days' => $data->days,
                'plan_ends_at' => $endsAt,
                'note' => $data->note ?? $link?->note,
            ]);
        });
    }
}
