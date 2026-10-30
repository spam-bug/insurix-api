<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Forms\UpdateResponseStatus;
use App\DataTransferObject\Forms\ResponseStatusData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Forms\UpdateResponseStatusRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class FormResponseStatusController extends Controller
{
    public function store(UpdateResponseStatusRequest $request, Workspace $workspace, UpdateResponseStatus $updateResponseStatus): JsonResponse
    {
        return response()->json(['data' => ['updated' => $updateResponseStatus->handle($workspace, ResponseStatusData::fromArray($request->validated()))]]);
    }
}
