<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\ListNotificationPreferences;
use App\Actions\Account\UpdateNotificationPreference;
use App\DataTransferObject\Account\NotificationPreferenceData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\UpdateNotificationPreferenceRequest;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;

/** Which kinds of notification the user gets in the app and by email, across workspaces. */
class NotificationPreferenceController extends Controller
{
    public function index(#[CurrentUser] User $user, ListNotificationPreferences $listNotificationPreferences): JsonResponse
    {
        return response()->json(['data' => $listNotificationPreferences->handle($user)]);
    }

    public function update(
        UpdateNotificationPreferenceRequest $request,
        #[CurrentUser] User $user,
        UpdateNotificationPreference $updateNotificationPreference,
        ListNotificationPreferences $listNotificationPreferences,
    ): JsonResponse {
        $user = $updateNotificationPreference->handle($user, NotificationPreferenceData::fromArray($request->validated()));

        return response()->json(['data' => $listNotificationPreferences->handle($user)]);
    }
}
