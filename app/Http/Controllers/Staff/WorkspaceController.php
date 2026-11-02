<?php

declare(strict_types=1);

namespace App\Http\Controllers\Staff;

use App\Actions\Staff\ListWorkspacesForStaff;
use App\Actions\Staff\LoadWorkspaceForStaff;
use App\Actions\Workspaces\MeasureUsage;
use App\DataTransferObject\Staff\StaffWorkspaceFilters;
use App\Http\Controllers\Controller;
use App\Http\Requests\Staff\ListStaffWorkspacesRequest;
use App\Http\Resources\Staff\StaffWorkspaceDetailResource;
use App\Http\Resources\Staff\StaffWorkspaceResource;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceController extends Controller
{
    public function index(ListStaffWorkspacesRequest $request, ListWorkspacesForStaff $listWorkspaces): Response
    {
        return Inertia::render('staff/workspaces/index', [
            'workspaces' => StaffWorkspaceResource::collection($listWorkspaces->handle(StaffWorkspaceFilters::fromArray($request->validated()))),
            'filters' => $request->validated(),
        ]);
    }

    public function show(Request $request, Workspace $workspace, LoadWorkspaceForStaff $loadWorkspace, MeasureUsage $measureUsage): Response
    {
        $workspace = $loadWorkspace->handle($workspace);

        return Inertia::render('staff/workspaces/show', [
            'workspace' => StaffWorkspaceDetailResource::make($workspace)->resolve($request),
            'usage' => $workspace->trashed() ? null : $measureUsage->handle($workspace),
            'can_change' => (bool) $request->user('staff')->can('change-accounts'),
        ]);
    }
}
