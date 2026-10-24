<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Models\Lead;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeleteLead
{
    public function handle(Lead $lead): void
    {
        DB::transaction(fn () => $lead->delete());

        Cache::tags("workspace:{$lead->workspace_id}")->flush();
    }
}
