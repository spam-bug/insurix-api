<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ListSessions;
use App\Actions\Account\RevokeSessions;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Laravel\Sanctum\PersonalAccessToken;

/** The devices the agent is signed in on, one per access token. */
class SessionController extends Controller
{
    public function index(#[CurrentUser] User $agent, ListSessions $listSessions): JsonResponse
    {
        return response()->json(['data' => $listSessions->handle($agent, $this->currentTokenId($agent))]);
    }

    /**
     * Signs out one other device. The current one signs out with the normal log-out.
     */
    public function destroy(int $session, #[CurrentUser] User $agent, RevokeSessions $revokeSessions): JsonResponse
    {
        $revoked = $revokeSessions->handle($agent, $session, $this->currentTokenId($agent));

        abort_if($revoked === 0, 404);

        return response()->json(['data' => ['revoked' => $revoked]]);
    }

    public function destroyOthers(#[CurrentUser] User $agent, RevokeSessions $revokeSessions): JsonResponse
    {
        return response()->json(['data' => ['revoked' => $revokeSessions->handle($agent, null, $this->currentTokenId($agent))]]);
    }

    private function currentTokenId(User $agent): ?int
    {
        $token = $agent->currentAccessToken();

        return $token instanceof PersonalAccessToken ? $token->id : null;
    }
}
