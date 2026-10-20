<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\DataTransferObject\Policies\PolicyData;
use App\Models\Policy;
use Illuminate\Support\Facades\DB;

class UpdatePolicy
{
    public function handle(Policy $policy, PolicyData $data): Policy
    {
        DB::transaction(fn () => $policy->update($data->toAttributes()));

        return $policy;
    }
}
