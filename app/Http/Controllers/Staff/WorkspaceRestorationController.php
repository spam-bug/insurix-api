<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\RecoverWorkspace;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\RecoverWorkspaceRequest;
use App\Models\Workspace;
use Illuminate\Http\RedirectResponse;

class WorkspaceRestorationController extends Controller
{
    public function store(RecoverWorkspaceRequest $request, Workspace $workspace, RecoverWorkspace $recoverWorkspace): RedirectResponse
    {
        $recoverWorkspace->handle($workspace, $request->user('staff'));

        return back()->with('status', "{$workspace->name} is restored");
    }
}
