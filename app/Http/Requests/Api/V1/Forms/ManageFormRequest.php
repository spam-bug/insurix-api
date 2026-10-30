<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

/** For form requests without input: publish, close, make default, new link, delete. Any member builds forms. */
class ManageFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
