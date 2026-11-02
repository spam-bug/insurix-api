<?php

declare(strict_types=1);

namespace App\Actions\Staff;

use App\Actions\Workspaces\RestoreWorkspace;
use App\Enums\StaffActivityType;
use App\Models\Staff;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class RecoverWorkspace
{
    public function __construct(
        private RestoreWorkspace $restoreWorkspace,
        private RecordStaffActivity $recordStaffActivity,
    ) {}

    /** Brings back a deleted workspace for its owner, before it's purged for good. */
    public function handle(Workspace $workspace, Staff $staff): Workspace
    {
        return DB::transaction(function () use ($workspace, $staff) {
            $this->restoreWorkspace->handle($workspace);
            $this->recordStaffActivity->handle($staff, StaffActivityType::WorkspaceRestored, $workspace);

            return $workspace;
        });
    }
}
