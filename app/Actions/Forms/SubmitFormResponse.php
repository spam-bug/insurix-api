<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Forms\FormResponseData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\FormResponseStatus;
use App\Models\Form;
use App\Models\FormResponse;
use Illuminate\Support\Facades\DB;

class SubmitFormResponse
{
    public function __construct(
        private AddResponseToLeads $addResponseToLeads,
        private AnnounceWorkspaceChange $announceWorkspaceChange,
    ) {}

    /**
     * Saves a visitor's answers with the fields they answered. When the form adds responses to
     * Leads and the answers are enough for a lead, it becomes one right away. A filled-in honeypot
     * is dropped silently, so bots get the same answer as people.
     */
    public function handle(Form $form, FormResponseData $data): ?FormResponse
    {
        if ($data->website !== null) {
            return null;
        }

        $response = DB::transaction(fn () => $form->responses()->create([
            'workspace_id' => $form->workspace_id,
            'status' => FormResponseStatus::New,
            'answers' => $data->answers,
            'fields' => $form->publishedFields(),
        ]));

        if ($form->published['settings']['auto_add_leads'] ?? false) {
            $this->addResponseToLeads->handle($response);
        }

        if ($response->status === FormResponseStatus::New) {
            $this->announceWorkspaceChange->handle($form->workspace_id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Created, $response->id, $form->name));
        }

        return $response;
    }
}
