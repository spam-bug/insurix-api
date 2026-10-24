<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\AssignDocuments;
use App\DataTransferObject\Documents\AssignDocumentsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Documents\AssignDocumentsRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class DocumentAssignmentController extends Controller
{
    public function store(AssignDocumentsRequest $request, Workspace $workspace, AssignDocuments $assignDocuments): JsonResponse
    {
        return response()->json(['assigned' => $assignDocuments->handle($workspace, AssignDocumentsData::fromArray($request->validated()))]);
    }
}
