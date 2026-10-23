<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class BulkDeleteLeads
{
    /**
     * Deletes the agent's own leads among $leadIds.
     *
     * @param  array<int, string>  $leadIds
     * @return int how many leads were deleted
     */
    public function handle(User $agent, array $leadIds): int
    {
        return DB::transaction(fn () => $agent->leads()->whereKey($leadIds)->delete());
    }
}
