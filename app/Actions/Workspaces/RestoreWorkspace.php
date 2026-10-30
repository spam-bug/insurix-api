<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class RestoreWorkspace
{
    /** Brings a deleted workspace back, as it was, while it's still within its grace period. */
    public function handle(Workspace $workspace): Workspace
    {
        DB::transaction(fn () => $workspace->restore());

        return $workspace;
    }
}
