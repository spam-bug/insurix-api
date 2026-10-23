<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\AddLeadNote;
use App\Actions\Leads\ListLeadActivities;
use App\Actions\Leads\ScheduleLeadMeeting;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\ScheduleLeadMeetingRequest;
use App\Http\Requests\Api\V1\Leads\StoreLeadNoteRequest;
use App\Http\Resources\Api\V1\LeadActivityResource;
use App\Http\Resources\Api\V1\LeadResource;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

class LeadActivityController extends Controller
{
    public function index(Lead $lead, ListLeadActivities $listLeadActivities): AnonymousResourceCollection
    {
        Gate::authorize('view', $lead);

        return LeadActivityResource::collection($listLeadActivities->handle($lead));
    }

    public function storeNote(StoreLeadNoteRequest $request, Lead $lead, #[CurrentUser] User $agent, AddLeadNote $addLeadNote): JsonResponse
    {
        Gate::authorize('update', $lead);

        return LeadActivityResource::make($addLeadNote->handle($lead, $request->body(), $agent)->load('user:id,name'))
            ->response()
            ->setStatusCode(201);
    }

    public function scheduleMeeting(ScheduleLeadMeetingRequest $request, Lead $lead, #[CurrentUser] User $agent, ScheduleLeadMeeting $scheduleLeadMeeting): LeadResource
    {
        Gate::authorize('update', $lead);

        return LeadResource::make($scheduleLeadMeeting->handle($lead, $request->toData(), $agent));
    }
}
