<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormFieldType;
use App\Enums\ImportField;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', app(Workspace::class));
    }

    /**
     * The draft's shape only: a draft may be half done (empty labels, choices still to add);
     * publishing checks that it's complete. Ids are short and safe to use as answer keys, and a
     * lead or client detail is linked to one field at most.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = ['required', 'string', 'regex:/^[A-Za-z0-9_-]{1,40}$/'];
        $field = 'draft.sections.*.fields.*';

        return [
            'name' => ['sometimes', 'string', 'max:120'],
            'draft' => ['sometimes', 'array:sections,settings'],
            'draft.sections' => ['required_with:draft', 'array', 'min:1', 'max:20'],
            'draft.sections.*' => ['array:id,title,description,fields'],
            'draft.sections.*.id' => [...$id, 'distinct'],
            'draft.sections.*.title' => ['nullable', 'string', 'max:200'],
            'draft.sections.*.description' => ['nullable', 'string', 'max:2000'],
            'draft.sections.*.fields' => ['present', 'array', 'max:100'],
            $field => ['array:id,type,label,help,placeholder,required,options,link'],
            "{$field}.id" => [...$id, 'distinct'],
            "{$field}.type" => ['required', Rule::enum(FormFieldType::class)],
            "{$field}.label" => ['nullable', 'string', 'max:2000'],
            "{$field}.help" => ['nullable', 'string', 'max:500'],
            "{$field}.placeholder" => ['nullable', 'string', 'max:200'],
            "{$field}.required" => ['boolean'],
            "{$field}.options" => ['present', 'array', 'max:50'],
            "{$field}.options.*" => ['array:id,label'],
            "{$field}.options.*.id" => $id,
            "{$field}.options.*.label" => ['required', 'string', 'max:200'],
            "{$field}.link" => ['nullable', Rule::in(array_column(ImportField::formLinks(), 'value')), 'distinct'],
            'draft.settings' => ['required_with:draft', 'array:title,description,submit_label,success_message,auto_add_leads'],
            'draft.settings.title' => ['nullable', 'string', 'max:200'],
            'draft.settings.description' => ['nullable', 'string', 'max:2000'],
            'draft.settings.submit_label' => ['nullable', 'string', 'max:40'],
            'draft.settings.success_message' => ['nullable', 'string', 'max:500'],
            'draft.settings.auto_add_leads' => ['boolean'],
        ];
    }
}
