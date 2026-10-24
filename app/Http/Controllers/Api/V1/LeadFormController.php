<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\GetLeadFormToken;
use App\Http\Controllers\Controller;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** The signed-in agent's own shareable form link. */
class LeadFormController extends Controller
{
    public function show(Workspace $workspace, GetLeadFormToken $getLeadFormToken): JsonResponse
    {
        return response()->json(['data' => ['token' => $getLeadFormToken->handle($workspace)]]);
    }
}
