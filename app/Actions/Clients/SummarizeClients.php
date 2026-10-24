<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Enums\PolicyStatus;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class SummarizeClients
{
    /**
     * The book at a glance: how many clients, how many hold a policy in force, their annual
     * premium in force, and how many were added this month (in the workspace's timezone).
     *
     * @return array{clients: int, with_policies: int, annual_premium: float, added_this_month: int}
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("clients:{$workspace->id}:summary", function () use ($workspace) {
            $startOfMonth = CarbonImmutable::now($workspace->timezone)->startOfMonth()->utc();
            $inForce = $workspace->policies()->where('status', PolicyStatus::InForce);

            return [
                'clients' => $workspace->clients()->count(),
                'with_policies' => (clone $inForce)->distinct()->count('client_id'),
                'annual_premium' => round((float) (clone $inForce)->sum('annual_premium'), 2),
                'added_this_month' => $workspace->clients()->where('created_at', '>=', $startOfMonth)->count(),
            ];
        });
    }
}
