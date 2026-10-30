<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\DownloadWorkspaceExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\ManageWorkspaceRequest;
use App\Models\WorkspaceExport;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WorkspaceExportDownloadController extends Controller
{
    public function show(ManageWorkspaceRequest $request, WorkspaceExport $export, DownloadWorkspaceExport $downloadWorkspaceExport): StreamedResponse
    {
        return $downloadWorkspaceExport->handle($export);
    }
}
