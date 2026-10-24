<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Documents\SummarizeDocuments;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

class DocumentSummaryController extends Controller
{
    public function index(Workspace $workspace, SummarizeDocuments $summarizeDocuments): JsonResponse
    {
        return response()->json(['data' => $summarizeDocuments->handle($workspace)]);
    }
}
