<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\Form;
use Illuminate\Support\Facades\DB;

class MakeDefaultForm
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** The default form is the one the share button opens; a workspace has exactly one. */
    public function handle(Form $form): Form
    {
        DB::transaction(function () use ($form) {
            Form::query()->where('workspace_id', $form->workspace_id)->whereKeyNot($form->id)->update(['is_default' => false]);
            $form->update(['is_default' => true]);
        });

        $this->announceWorkspaceChange->handle($form->workspace_id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Updated, $form->id, $form->name));

        return $form;
    }
}
