<?php

declare(strict_types=1);

namespace App\Actions\Leads;

use App\Actions\FollowUps\CreateFollowUp;
use App\DataTransferObjects\FollowUps\FollowUpData;
use App\DataTransferObjects\Leads\LeadMeetingData;
use App\Enums\FollowUpType;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ScheduleLeadMeeting
{
    public function __construct(
        private RecordLeadActivity $recordLeadActivity,
        private CreateFollowUp $createFollowUp,
    ) {}

    /**
     * Sets the meeting time, moves an open lead to "meeting scheduled", and puts the meeting
     * on the agent's follow-ups. Rescheduling replaces the open meeting follow-up. A lead that
     * is already a client keeps its status.
     */
    public function handle(Lead $lead, LeadMeetingData $data, User $actor): Lead
    {
        return DB::transaction(function () use ($lead, $data, $actor) {
            $previousStatus = $lead->status;

            $lead->update([
                'meeting_at' => $data->scheduledAt,
                'status' => $previousStatus === LeadStatus::Converted ? LeadStatus::Converted : LeadStatus::MeetingScheduled,
            ]);

            $lead->followUps()->whereNull('completed_at')->where('type', FollowUpType::Meeting)->delete();

            $this->createFollowUp->handle($lead, new FollowUpData(
                type: FollowUpType::Meeting,
                title: "Meeting with {$lead->name}",
                dueAt: $data->scheduledAt,
                notes: $data->notes,
            ));

            $this->recordLeadActivity->handle($lead, LeadActivityType::MeetingScheduled, $actor, $data->notes, [
                'scheduled_at' => $data->scheduledAt->toIso8601String(),
                'previous_status' => $previousStatus->value,
            ]);

            return $lead;
        });
    }
}
