<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Policies;

class UpdatePolicyRequest extends StorePolicyRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('policy'));
    }
}
