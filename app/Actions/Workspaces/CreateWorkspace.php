<?php

declare(strict_types=1);

namespace App\Actions\Workspaces;

use App\Actions\Forms\CreateForm;
use App\Actions\Forms\PublishForm;
use App\DataTransferObject\Forms\FormData;
use App\DataTransferObject\Workspaces\WorkspaceData;
use App\Enums\FormTemplate;
use App\Enums\WorkspaceRole;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

class CreateWorkspace
{
    public function __construct(
        private CreateForm $createForm,
        private PublishForm $publishForm,
    ) {}

    /**
     * Creates a workspace of the given type with the user as its owner, and a published contact
     * form as its default, so the share button works from the start.
     */
    public function handle(User $owner, WorkspaceData $data): Workspace
    {
        return DB::transaction(function () use ($owner, $data) {
            $workspace = Workspace::create($data->toArray());
            $workspace->users()->attach($owner, ['role' => WorkspaceRole::Owner]);

            $this->publishForm->handle($this->createForm->handle($workspace, new FormData(FormTemplate::Contact->label(), FormTemplate::Contact)));

            return $workspace;
        });
    }
}
