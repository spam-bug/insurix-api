<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\GetLeadFormToken;
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
}
