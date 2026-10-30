<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\FormStatus;
use App\Models\Form;
use Illuminate\Support\Facades\DB;

class CloseForm
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** Stops taking responses. The link keeps working and says the form is closed; publishing opens it again. */
    public function handle(Form $form): Form
    {
        DB::transaction(fn () => $form->update(['status' => FormStatus::Closed]));

        $this->announceWorkspaceChange->handle($form->workspace_id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Updated, $form->id, $form->name));

        return $form;
    }
}
