<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\WorkspaceInvitation;
use Illuminate\Support\Facades\DB;

class RevokeInvitation
{
    /** Withdraws a pending invite, so its link stops working. */
    public function handle(WorkspaceInvitation $invitation): void
    {
        DB::transaction(fn () => $invitation->delete());
    }
}
