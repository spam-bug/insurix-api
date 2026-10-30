<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Forms\UpdateFormData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\ImportField;
use App\Models\Form;
use Illuminate\Support\Facades\DB;

class UpdateForm
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Saves the builder's draft (it autosaves) or the form's name. The published form doesn't
     * change until it's published again. A linked field always keeps its link's type and choices.
     */
    public function handle(Form $form, UpdateFormData $data): Form
    {
        DB::transaction(fn () => $form->update(array_filter([
            'name' => $data->name,
            'draft' => $data->draft ? $this->withLinkedTypes($data->draft) : null,
        ], fn (mixed $value) => $value !== null)));

        $this->announceWorkspaceChange->handle($form->workspace_id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Updated, $form->id, $form->name));

        return $form;
    }

    /**
     * @param  array{sections: list<array<string, mixed>>, settings: array<string, mixed>}  $draft
     * @return array{sections: list<array<string, mixed>>, settings: array<string, mixed>}
     */
    private function withLinkedTypes(array $draft): array
    {
        foreach ($draft['sections'] as $sectionIndex => $section) {
            foreach ($section['fields'] as $fieldIndex => $field) {
                $link = ImportField::tryFrom($field['link'] ?? '');

                if ($link !== null) {
                    $draft['sections'][$sectionIndex]['fields'][$fieldIndex]['type'] = $link->formFieldType()->value;
                    $draft['sections'][$sectionIndex]['fields'][$fieldIndex]['options'] = $link->formOptions();
                }
            }
        }

        return $draft;
    }
}
