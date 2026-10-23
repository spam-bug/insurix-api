<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\RevokeSessions;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** Signs out every device except the current one. */
class OtherSessionsController extends Controller
{
    public function destroy(#[CurrentUser] User $agent, RevokeSessions $revokeSessions): JsonResponse
    {
        return response()->json(['data' => ['revoked' => $revokeSessions->handle($agent)]]);
    }
}
