<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Insights\SuggestNextActions;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

class NextActionController extends Controller
{
    public function index(#[CurrentUser] User $agent, SuggestNextActions $suggestNextActions): JsonResponse
    {
        return response()->json(['data' => $suggestNextActions->handle($agent)]);
    }
}
