<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\LeadSelectionData;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BulkDeleteLeads
{
    /**
     * Deletes the agent's own leads among the selected ones.
     *
     * @return int how many leads were deleted
     */
    public function handle(User $agent, LeadSelectionData $data): int
    {
        $deleted = DB::transaction(fn () => $agent->leads()->whereKey($data->ids)->delete());

        Cache::tags("agent:{$agent->id}")->flush();

        return $deleted;
    }
}
