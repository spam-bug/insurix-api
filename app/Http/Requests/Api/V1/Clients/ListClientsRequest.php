<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Clients;

use App\DataTransferObjects\Clients\ClientFiltersData;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ListClientsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
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

    public function toData(): ClientFiltersData
    {
        return new ClientFiltersData(
            search: $this->filled('search') ? $this->string('search')->trim()->toString() : null,
            perPage: $this->integer('per_page', 10),
        );
    }
}
