<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\FollowUps\CountFollowUpsByDay;
use App\Enums\FollowUpType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FollowUps\ListFollowUpsRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;

/** Counts per day for the Follow-ups week strip; takes the same type filter as the list. */
class FollowUpCalendarController extends Controller
{
    public function index(ListFollowUpsRequest $request, Workspace $workspace, CountFollowUpsByDay $countFollowUpsByDay): JsonResponse
    {
        return response()->json(['data' => $countFollowUpsByDay->handle($workspace, $request->enum('type', FollowUpType::class))]);
    }
}
