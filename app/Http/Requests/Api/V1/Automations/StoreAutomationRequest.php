<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Automations;

use App\Enums\AutomationActionType;
use App\Enums\AutomationField;
use App\Enums\AutomationMatch;
use App\Enums\AutomationOperator;
use App\Enums\AutomationTrigger;
use App\Enums\FollowUpType;
use App\Enums\LeadStatus;
use App\Models\Automation;
use App\Models\Workspace;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAutomationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Automation::class, app(Workspace::class)]);
    }

    /**
     * Each condition and action is checked for its own kind: a condition's operator must suit
     * its field (a score is "at least", a source "is"), and each action needs its own details.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $manualStages = Rule::enum(LeadStatus::class)->except(LeadStatus::Converted);

        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'trigger' => ['required', Rule::enum(AutomationTrigger::class)],
            'trigger_stage' => ['exclude_unless:trigger,lead_stage_changed', 'nullable', $manualStages],
            'idle_days' => ['exclude_unless:trigger,lead_idle', 'required', 'integer', 'min:1', 'max:365'],
            'match' => ['required', Rule::enum(AutomationMatch::class)],
            'conditions' => ['present', 'array', 'max:10'],
            'conditions.*' => ['array'],
            'conditions.*.field' => ['required', Rule::enum(AutomationField::class)],
            'actions' => ['required', 'array', 'min:1', 'max:5'],
            'actions.*' => ['array'],
            'actions.*.type' => ['required', Rule::enum(AutomationActionType::class)],
        ];

        foreach ((array) $this->input('conditions', []) as $index => $condition) {
            $field = AutomationField::tryFrom((string) ($condition['field'] ?? ''));
            $operator = AutomationOperator::tryFrom((string) ($condition['operator'] ?? ''));

            $rules["conditions.{$index}.operator"] = ['required', Rule::in(array_map(fn (AutomationOperator $option) => $option->value, $field?->operators() ?? AutomationOperator::cases()))];

            if ($field && $operator?->needsValue()) {
                $rules["conditions.{$index}.value"] = $field->options()
                    ? ['required', Rule::enum($field->options())]
                    : ['required', 'integer', 'min:0', 'max:10000'];
            }
        }

        foreach ((array) $this->input('actions', []) as $index => $action) {
            $rules += match (AutomationActionType::tryFrom((string) ($action['type'] ?? ''))) {
                AutomationActionType::AddFollowUp => [
                    "actions.{$index}.follow_up_type" => ['required', Rule::enum(FollowUpType::class)],
                    "actions.{$index}.title" => ['required', 'string', 'max:255'],
                    "actions.{$index}.due_in_hours" => ['required', 'integer', 'min:0', 'max:720'],
                ],
                AutomationActionType::MoveToStage => ["actions.{$index}.stage" => ['required', $manualStages]],
                AutomationActionType::AddNote => ["actions.{$index}.body" => ['required', 'string', 'max:2000']],
                AutomationActionType::NotifyTeam => ["actions.{$index}.message" => ['required', 'string', 'max:255']],
                null => [],
            };
        }

        return $rules;
    }
}
