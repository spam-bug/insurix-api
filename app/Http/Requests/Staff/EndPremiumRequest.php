<?php

declare(strict_types=1);

namespace App\Http\Requests\Staff;

use App\Enums\Plan;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class EndPremiumRequest extends FormRequest
{
    /** Admins only, on a workspace that has Premium. */
    public function authorize(): bool
    {
        /** @var Workspace $workspace */
        $workspace = $this->route('workspace');

        return $this->user('staff')?->can('change-accounts') && $workspace->currentPlan() === Plan::Premium;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }
}
