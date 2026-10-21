<?php

declare(strict_types=1);

namespace App\Actions\Applications;

use App\Models\ApplicationRequirement;
use Illuminate\Support\Facades\DB;

class DeleteRequirement
{
    public function handle(ApplicationRequirement $requirement): void
    {
        DB::transaction(fn () => $requirement->delete());
    }
}
