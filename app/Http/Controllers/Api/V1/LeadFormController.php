<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\GetLeadFormToken;
use App\Actions\LeadForms\RegenerateLeadFormToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** The signed-in agent's own shareable form link. */
class LeadFormController extends Controller
{
    public function show(#[CurrentUser] User $agent, GetLeadFormToken $getLeadFormToken): JsonResponse
    {
        return response()->json(['data' => ['token' => $getLeadFormToken->handle($agent)]]);
    }

    public function regenerate(#[CurrentUser] User $agent, RegenerateLeadFormToken $regenerateLeadFormToken): JsonResponse
    {
        return response()->json(['data' => ['token' => $regenerateLeadFormToken->handle($agent)]]);
    }
}
