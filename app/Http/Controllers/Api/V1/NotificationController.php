<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Notifications\ListNotifications;
use App\Actions\Notifications\MarkNotificationRead;
use App\DataTransferObject\Notifications\NotificationFiltersData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Notifications\ListNotificationsRequest;
use App\Http\Requests\Api\V1\Notifications\UpdateNotificationRequest;
use App\Http\Resources\Api\V1\NotificationResource;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(ListNotificationsRequest $request, Workspace $workspace, #[CurrentUser] User $user, ListNotifications $listNotifications): AnonymousResourceCollection
    {
        return NotificationResource::collection($listNotifications->handle($user, $workspace, NotificationFiltersData::fromArray($request->validated())));
    }

    /** Marks the notification read. */
    public function update(UpdateNotificationRequest $request, DatabaseNotification $notification, MarkNotificationRead $markNotificationRead): NotificationResource
    {
        return NotificationResource::make($markNotificationRead->handle($notification));
    }
}
