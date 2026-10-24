<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\ScheduleLeadMeeting;
use App\DataTransferObject\Leads\LeadMeetingData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Leads\ScheduleLeadMeetingRequest;
use App\Http\Resources\Api\V1\LeadResource;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Container\Attributes\CurrentUser;

class LeadMeetingController extends Controller
{
    public function store(ScheduleLeadMeetingRequest $request, Lead $lead, #[CurrentUser] User $user, ScheduleLeadMeeting $scheduleLeadMeeting): LeadResource
    {
        return LeadResource::make($scheduleLeadMeeting->handle($lead, LeadMeetingData::fromArray($request->validated()), $user));
    }
}
