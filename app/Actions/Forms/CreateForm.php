<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Forms\FormData;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Enums\FormStatus;
use App\Enums\FormTemplate;
use App\Models\Form;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateForm
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /**
     * Starts a draft from the template, or from a blank form. The workspace's first form becomes
     * its default. Free workspaces have a limit on forms.
     */
    public function handle(Workspace $workspace, FormData $data): Form
    {
        $limit = $workspace->currentPlan()->formLimit();

        $form = DB::transaction(function () use ($workspace, $data, $limit) {
            $count = $workspace->forms()->lockForUpdate()->count();

            if ($limit !== null && $count >= $limit) {
                throw ValidationException::withMessages(['name' => "Free workspaces can have up to {$limit} forms."]);
            }

            $draft = $data->template?->document() ?? FormTemplate::blankDocument($data->name);

            return $workspace->forms()->create([
                'name' => $data->name,
                'token' => Str::random(40),
                'status' => FormStatus::Draft,
                'is_default' => $count === 0,
                'template' => $data->template,
                'draft' => $draft,
            ]);
        });

        $this->announceWorkspaceChange->handle($workspace->id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Created, $form->id, $form->name));

        return $form;
    }
}
