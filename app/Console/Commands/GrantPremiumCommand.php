<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Premium\GrantPremium;
use App\Actions\Premium\RevokePremium;
use App\DataTransferObject\Premium\PremiumGrantData;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

#[Signature('premium:grant
    {email : The account whose own workspaces get Premium}
    {--workspace= : Only this workspace (an id), which the account must own}
    {--days= : How many days of Premium}
    {--years= : Or how many years}
    {--forever : Premium with no end date}
    {--revoke : Put the workspaces back on Free instead}
    {--note= : Why, such as "Beta tester"}')]
#[Description('Give Premium to the workspaces an account owns, or take it away')]
class GrantPremiumCommand extends Command
{
    public function handle(GrantPremium $grantPremium, RevokePremium $revokePremium): int
    {
        $user = User::query()->where('email', $this->argument('email'))->first();
        $workspaces = $user ? $this->ownedWorkspaces($user) : new Collection;

        if ($workspaces->isEmpty()) {
            $this->error('No workspace owned by that account.');

            return self::FAILURE;
        }

        if ($this->option('revoke')) {
            $workspaces->each(fn (Workspace $workspace) => $revokePremium->handle($workspace));
            $this->info("Back on Free: {$workspaces->pluck('name')->join(', ')}");

            return self::SUCCESS;
        }

        $days = PremiumDuration::days($this);

        if ($days === false) {
            return self::INVALID;
        }

        foreach ($workspaces as $workspace) {
            $grant = $grantPremium->handle($workspace, new PremiumGrantData($days, $this->option('note')), $user);
            $this->info("{$workspace->name}: Premium ".($grant->plan_ends_at ? "until {$grant->plan_ends_at->toDateString()}" : 'forever'));
        }

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function ownedWorkspaces(User $user): Collection
    {
        return $user->workspaces()
            ->wherePivot('role', WorkspaceRole::Owner->value)
            ->when($this->option('workspace'), fn ($query, string $id) => $query->whereKey($id))
            ->get();
    }
}
