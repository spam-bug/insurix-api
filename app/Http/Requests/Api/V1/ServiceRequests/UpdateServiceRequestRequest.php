<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\ServiceRequests;

use App\Enums\ServiceRequestStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class UpdateServiceRequestRequest extends StoreServiceRequestRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('serviceRequest'));
    }

    /**
     * An edit sends the full request, status included.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'status' => ['required', Rule::enum(ServiceRequestStatus::class)],
        ];
    }
}
