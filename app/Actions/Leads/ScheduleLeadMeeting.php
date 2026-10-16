<?php

namespace App\Actions\Leads;

use App\DataTransferObjects\Leads\LeadMeetingData;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScheduleLeadMeeting
{
    public function __construct(private RecordLeadActivity $recordLeadActivity) {}

    /**
     * Sets the meeting time and moves the lead to "meeting scheduled".
     */
    public function handle(Lead $lead, LeadMeetingData $data, User $actor): Lead
    {
        return DB::transaction(function () use ($lead, $data, $actor) {
            $previousStatus = $lead->status;

            $lead->update([
                'meeting_at' => $data->scheduledAt,
                'status' => LeadStatus::MeetingScheduled,
            ]);

            $this->recordLeadActivity->handle($lead, LeadActivityType::MeetingScheduled, $actor, $data->notes, [
                'scheduled_at' => $data->scheduledAt->toIso8601String(),
                'previous_status' => $previousStatus->value,
            ]);

            return $lead;
        });
    }
}
