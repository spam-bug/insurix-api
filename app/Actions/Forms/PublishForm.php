<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\FormFieldType;
use App\Enums\FormStatus;
use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishForm
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Makes the draft what visitors see. Drafts may be half done; a published form needs at least
     * one question, and every question needs a label and its choices. Linking fields to leads or
     * clients is optional: forms are also surveys, sign-ups and the like.
     */
    public function handle(Form $form): Form
    {
        $problems = $this->problems($form->draft);

        if ($problems !== []) {
            throw ValidationException::withMessages(['draft' => $problems]);
        }

        DB::transaction(fn () => $form->update([
            'published' => $form->draft,
            'status' => FormStatus::Published,
            'published_at' => now(),
        ]));

        $this->announceWorkspaceChange->handle($form->workspace_id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Updated, $form->id, $form->name));

        return $form;
    }

    /**
     * @param  array{sections: list<array<string, mixed>>, settings: array<string, mixed>, theme?: array<string, mixed>}  $draft
     * @return list<string>
     */
    private function problems(array $draft): array
    {
        $fields = array_merge(...array_column($draft['sections'], 'fields'));
        $problems = [];

        if (array_filter($fields, fn (array $field) => ! FormFieldType::from($field['type'])->isLayout()) === []) {
            $problems[] = 'Add at least one question.';
        }

        foreach ($fields as $field) {
            $type = FormFieldType::from($field['type']);

            if ($type !== FormFieldType::Divider && blank($field['label'] ?? null)) {
                $problems[] = "Every {$type->label()} needs a label.";
            }
            if ($type->hasOptions() && empty($field['options'])) {
                $problems[] = "“{$field['label']}” needs at least one choice.";
            }
        }

        return array_values(array_unique($problems));
    }
}
