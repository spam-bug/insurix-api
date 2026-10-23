<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\Models\Policy;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DeletePolicy
{
    public function handle(Policy $policy): void
    {
        DB::transaction(fn () => $policy->delete());

        Cache::tags("agent:{$policy->user_id}")->flush();
    }
}
