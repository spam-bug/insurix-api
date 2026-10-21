<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ListSessions;
use App\Actions\Account\RevokeSessions;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** The devices the agent is signed in on, one per browser session. */
class SessionController extends Controller
{
    public function index(#[CurrentUser] User $agent, ListSessions $listSessions): JsonResponse
    {
        return response()->json(['data' => $listSessions->handle($agent)]);
    }

    /**
     * Signs out one other device. The current one signs out with the normal log-out.
     */
    public function destroy(string $session, #[CurrentUser] User $agent, RevokeSessions $revokeSessions): JsonResponse
    {
        return response()->json(['data' => ['revoked' => $revokeSessions->handle($agent, $session)]]);
    }
}
