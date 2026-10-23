<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\LeadForms\RegenerateLeadFormToken;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class LeadFormTokenController extends Controller
{
    public function store(#[CurrentUser] User $agent, RegenerateLeadFormToken $regenerateLeadFormToken): JsonResponse
    {
        return response()->json(['data' => ['token' => $regenerateLeadFormToken->handle($agent)]]);
    }
}
