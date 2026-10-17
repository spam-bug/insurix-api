<?php

namespace App\Actions\Policies;

use App\DataTransferObjects\Policies\PolicyData;
use App\Models\Policy;

class UpdatePolicy
{
    public function handle(Policy $policy, PolicyData $data): Policy
    {
        $policy->update($data->toAttributes());

        return $policy;
    }
}
