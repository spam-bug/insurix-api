<?php

declare(strict_types=1);

namespace App\Actions\Forms;

use App\Actions\Notifications\AnnounceWorkspaceChange;
use App\DataTransferObject\Workspaces\WorkspaceChangeData;
use App\Enums\ChangeAction;
use App\Enums\ChangeTopic;
use App\Models\FormResponse;
use Illuminate\Support\Facades\DB;

class DeleteFormResponse
{
    public function __construct(private AnnounceWorkspaceChange $announceWorkspaceChange) {}

    /** Deletes the answers, for example when someone asks to be forgotten. A lead or client made from them stays. */
    public function handle(FormResponse $response): void
    {
        DB::transaction(fn () => $response->delete());

        $this->announceWorkspaceChange->handle($response->workspace_id, new WorkspaceChangeData(ChangeTopic::Forms, ChangeAction::Deleted, $response->id));
    }
}
