<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Forms;

use App\Enums\FormFieldType;
use App\Enums\FormStatus;
use App\Enums\ImportField;
use App\Models\Form;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitFormResponseRequest extends FormRequest
{
    /** Anyone may answer a published form; a closed one takes no more responses. */
    public function authorize(): bool
    {
        return $this->form()->status === FormStatus::Published;
    }

    /**
     * Rules come from the published form: each question's type, whether it's required and its
     * choices. A form that adds responses to Leads and asks for both email and phone needs one
     * of them, so the lead can be reached.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $questions = array_filter($this->form()->publishedFields(), fn (array $field) => ! FormFieldType::from($field['type'])->isLayout());
        $addsLeads = $this->form()->published['settings']['auto_add_leads'] ?? false;
        $contactIds = $addsLeads ? array_column(array_filter($questions, fn (array $field) => in_array($field['link'] ?? null, [ImportField::Email->value, ImportField::Phone->value], true)), 'id') : [];
        $rules = [
            'answers' => ['array:'.implode(',', array_column($questions, 'id'))],
            'website' => ['nullable', 'string', 'max:255'],
        ];

        foreach ($questions as $field) {
            $otherContactIds = array_values(array_diff($contactIds, [$field['id']]));
            $presence = match (true) {
                $field['required'] ?? false => 'required',
                count($contactIds) === 2 && count($otherContactIds) === 1 => "required_without:answers.{$otherContactIds[0]}",
                default => 'nullable',
            };

            $rules["answers.{$field['id']}"] = [$presence, ...$this->typeRules($field)];

            if (FormFieldType::from($field['type']) === FormFieldType::MultipleChoice) {
                $rules["answers.{$field['id']}.*"] = [Rule::in(array_column($field['options'] ?? [], 'id'))];
            }
        }

        return $rules;
    }

    private function form(): Form
    {
        return $this->route('form');
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    private function typeRules(array $field): array
    {
        return match (FormFieldType::from($field['type'])) {
            FormFieldType::ShortText => ['string', 'max:255'],
            FormFieldType::LongText => ['string', 'max:5000'],
            FormFieldType::Email => ['string', 'email', 'max:255'],
            FormFieldType::Phone => ['string', 'max:50'],
            FormFieldType::Number => ['numeric'],
            FormFieldType::Date => ['date'],
            FormFieldType::Dropdown, FormFieldType::SingleChoice => ['string', Rule::in(array_column($field['options'] ?? [], 'id'))],
            FormFieldType::MultipleChoice => ['array'],
            FormFieldType::YesNo => ['boolean'],
            FormFieldType::Consent => ($field['required'] ?? false) ? ['accepted'] : ['boolean'],
            default => [],
        };
    }
}
