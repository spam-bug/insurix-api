<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Models\Staff;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListStaffPremiumLinksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('staff') instanceof Staff;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
