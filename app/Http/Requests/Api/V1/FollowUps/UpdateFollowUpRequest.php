<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\FollowUps;

class UpdateFollowUpRequest extends StoreFollowUpRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('followUp'));
    }
}
