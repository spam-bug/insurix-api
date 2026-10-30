<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DeleteForm
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** Deletes the form and its responses; leads made from them stay. The default form can't go. */
    public function handle(Form $form): void
    {
        if ($form->is_default) {
            throw ValidationException::withMessages(['form' => 'Make another form the default before deleting this one.']);
        }

        DB::transaction(fn () => $form->delete());

        $this->announceWorkspaceChange->handle($form->workspace_id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Deleted, $form->id, $form->name));
    }
}
