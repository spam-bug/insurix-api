<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Workspaces\TransferOwnership;
use App\DataTransferObject\Workspaces\OwnershipData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Workspaces\TransferOwnershipRequest;
use App\Models\Workspace;
use Illuminate\Http\Response;

class WorkspaceOwnershipController extends Controller
{
    public function store(TransferOwnershipRequest $request, Workspace $workspace, TransferOwnership $transferOwnership): Response
    {
        $transferOwnership->handle($workspace, OwnershipData::fromArray($request->validated()));

        return response()->noContent();
    }
}
