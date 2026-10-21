<?php

declare(strict_types=1);

namespace App\Actions\Policies;

use App\DataTransferObject\Policies\PolicyData;
use App\Models\Policy;
use Illuminate\Support\Facades\DB;

class UpdatePolicy
{
    public function __construct(private PreparePolicyAttributes $preparePolicyAttributes) {}

    public function handle(Policy $policy, PolicyData $data): Policy
    {
        $attributes = $this->preparePolicyAttributes->handle($policy->user, $data, $policy);

        DB::transaction(fn () => $policy->update($attributes));

        return $policy;
    }
}
