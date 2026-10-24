<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\CountUnreadNotifications;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** The number on the bell. */
class UnreadNotificationCountController extends Controller
{
    public function index(Workspace $workspace, #[CurrentUser] User $user, CountUnreadNotifications $countUnreadNotifications): JsonResponse
    {
        return response()->json(['data' => ['count' => $countUnreadNotifications->handle($user, $workspace)]]);
    }
}
