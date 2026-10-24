<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\DataTransferObject\Leads\LeadSelectionData;
use App\Models\Workspace;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class BulkDeleteLeads
{
    /**
     * Deletes the workspace's leads among the selected ones.
     *
     * @return int how many leads were deleted
     */
    public function handle(Workspace $workspace, LeadSelectionData $data): int
    {
        $deleted = DB::transaction(fn () => $workspace->leads()->whereKey($data->ids)->delete());

        Cache::tags("workspace:{$workspace->id}")->flush();

        return $deleted;
    }
}
