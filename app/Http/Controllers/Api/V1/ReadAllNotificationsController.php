<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\MarkAllNotificationsRead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\ReadAllNotificationsRequest;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** "Mark all as read" in the bell. */
class ReadAllNotificationsController extends Controller
{
    public function store(ReadAllNotificationsRequest $request, Workspace $workspace, #[CurrentUser] User $user, MarkAllNotificationsRead $markAllNotificationsRead): JsonResponse
    {
        return response()->json(['data' => ['marked' => $markAllNotificationsRead->handle($user, $workspace)]]);
    }
}
