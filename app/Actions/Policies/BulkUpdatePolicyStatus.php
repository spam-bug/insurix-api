<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\DataTransferObject\Policies\BulkPolicyStatusData;
use App\Enums\PolicyStatus;
use App\Models\Workspace;
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
    public function handle(Workspace $workspace, BulkPolicyStatusData $data): int
    {
        $updated = DB::transaction(function () use ($workspace, $data) {
            $policies = $workspace->policies()
                ->whereKey($data->ids)
                ->whereIn('status', PolicyStatus::bulkSettable())
                ->where('status', '!=', $data->status)
                ->get();

            $policies->each->update(['status' => $data->status]);

            return $policies->count();
        });

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $updated;
    }
}
