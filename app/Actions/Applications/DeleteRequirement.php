<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Models\ApplicationRequirement;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeleteRequirement
{
    public function handle(ApplicationRequirement $requirement): void
    {
        DB::transaction(fn () => $requirement->delete());

        Cache::tags("workspace:{$requirement->policy->workspace_id}")->flush();
    }
}
