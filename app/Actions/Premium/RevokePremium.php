<?php

declare(strict_types=1);

namespace App\Actions\Premium;

use App\Enums\Plan;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class RevokePremium
{
    /** Puts the workspace back on Free right away. The grant history stays. */
    public function handle(Workspace $workspace): void
    {
        DB::transaction(fn () => $workspace->forceFill(['plan' => Plan::Free, 'plan_ends_at' => null])->save());
    }
}
