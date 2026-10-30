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
use App\Notifications\FormResponseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class SubmitFormResponse
{
    public function __construct(
        private AddResponseToLeads $addResponseToLeads,
        private AnnounceWorkspaceChange $announceWorkspaceChange,
    ) {}

    /**
     * Saves a visitor's answers with the fields they answered. When the form adds responses to
     * Leads and the answers are enough for a lead, it becomes one (or fills in the person already
     * there) right away; otherwise everyone hears about the new response through the bell. A
     * filled-in honeypot is dropped silently, so bots get the same answer as people.
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
            Notification::send($form->workspace->users, new FormResponseNotification($response, $form->name));
        }

        return $response;
    }
}
