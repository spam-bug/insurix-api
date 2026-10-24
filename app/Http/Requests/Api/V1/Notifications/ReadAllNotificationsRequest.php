<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Notifications;

use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ReadAllNotificationsRequest extends FormRequest
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
