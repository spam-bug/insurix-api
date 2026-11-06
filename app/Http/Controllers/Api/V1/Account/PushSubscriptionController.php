<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Account;

use App\Actions\Account\DeletePushSubscription;
use App\Actions\Account\SavePushSubscription;
use App\DataTransferObject\Account\PushSubscriptionData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\DeletePushSubscriptionRequest;
use App\Http\Requests\Api\V1\Account\StorePushSubscriptionRequest;
use Illuminate\Http\Response;

/** The devices a user gets push notifications on, one subscription per browser or installed app. */
class PushSubscriptionController extends Controller
{
    public function store(StorePushSubscriptionRequest $request, SavePushSubscription $savePushSubscription): Response
    {
        $savePushSubscription->handle($request->user(), PushSubscriptionData::fromArray($request->validated()));

        return response()->noContent();
    }

    public function destroy(DeletePushSubscriptionRequest $request, DeletePushSubscription $deletePushSubscription): Response
    {
        $deletePushSubscription->handle($request->user(), $request->validated('endpoint'));

        return response()->noContent();
    }
}
