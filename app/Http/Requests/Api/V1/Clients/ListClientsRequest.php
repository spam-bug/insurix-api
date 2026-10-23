<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Clients;

use App\Models\Client;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListClientsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Client::class);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
