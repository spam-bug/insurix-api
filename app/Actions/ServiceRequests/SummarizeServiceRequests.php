<?php

declare(strict_types=1);

namespace App\Actions\ServiceRequests;

use App\Enums\ServiceRequestStatus;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

class SummarizeServiceRequests
{
    /**
     * Counts for the tabs and the nav badge.
     *
     * @return array{unresolved: int, overdue: int, waiting_on_insurer: int}
     */
    public function handle(Workspace $workspace): array
    {
        $today = CarbonImmutable::now($workspace->timezone)->startOfDay();

        return Cache::tags("workspace:{$workspace->id}")->remember("service-requests:{$workspace->id}:summary:{$today->toDateString()}", now()->addHour(), fn () => [
            'unresolved' => $workspace->serviceRequests()->unresolved()->count(),
            'overdue' => $workspace->serviceRequests()->overdue($today)->count(),
            'waiting_on_insurer' => $workspace->serviceRequests()->where('status', ServiceRequestStatus::WaitingOnInsurer)->count(),
        ]);
    }
}
