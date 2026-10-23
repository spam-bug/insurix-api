<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\DataTransferObject\Policies\BulkPolicyStatusData;
use App\Enums\PolicyStatus;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BulkUpdatePolicyStatus
{
    /**
     * Moves the selected policies between in force, lapsed and cancelled. Pending and declined
     * policies are skipped, since applications are approved or declined through their own steps.
     *
     * @return int how many policies were changed
     */
    public function handle(User $agent, BulkPolicyStatusData $data): int
    {
        $updated = DB::transaction(function () use ($agent, $data) {
            $policies = $agent->policies()
                ->whereKey($data->ids)
                ->whereIn('status', PolicyStatus::bulkSettable())
                ->where('status', '!=', $data->status)
                ->get();

            $policies->each->update(['status' => $data->status]);

            return $policies->count();
        });

        Cache::tags("agent:{$agent->id}")->flush();

        return $updated;
    }
}
