<?php

namespace App\Actions\Leads;

use App\Models\User;

class BulkDeleteLeads
{
    /**
     * Deletes the agent's own leads among $leadIds.
     *
     * @param  array<int, int>  $leadIds
     * @return int how many leads were deleted
     */
    public function handle(User $agent, array $leadIds): int
    {
        return $agent->leads()->whereKey($leadIds)->delete();
    }
}
