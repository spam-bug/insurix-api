<?php

namespace App\Actions\Policies;

use App\Models\Policy;

class DeletePolicy
{
    public function handle(Policy $policy): void
    {
        $policy->delete();
    }
}
