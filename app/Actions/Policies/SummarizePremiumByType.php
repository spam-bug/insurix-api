<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Enums\PolicyStatus;
use App\Enums\PolicyType;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;

class SummarizePremiumByType
{
    /**
     * The annual premium in force per policy type, largest first. Types without in-force policies are left out.
     *
     * @return array<int, array{type: array{value: string, label: string}, policies: int, annual_premium: float}>
     */
    public function handle(Workspace $workspace): array
    {
        return Cache::tags("workspace:{$workspace->id}")->rememberForever("policies:{$workspace->id}:premium-by-type", function () use ($workspace) {
            return $workspace->policies()
                ->toBase()
                ->where('status', PolicyStatus::InForce)
                ->selectRaw('type, count(*) as policies, sum(annual_premium) as annual_premium')
                ->groupBy('type')
                ->orderByDesc('annual_premium')
                ->get()
                ->map(fn (object $row) => [
                    'type' => ['value' => $row->type, 'label' => PolicyType::from($row->type)->label()],
                    'policies' => (int) $row->policies,
                    'annual_premium' => round((float) $row->annual_premium, 2),
                ])
                ->all();
        });
    }
}
