<?php

declare(strict_types=1);

namespace App\Actions\Automations;

use App\Actions\FollowUps\CreateFollowUp;
use App\Actions\Leads\UpdateLeadStatus;
use App\Actions\Notes\CreateNote;
use App\DataTransferObject\FollowUps\FollowUpData;
use App\DataTransferObject\Notes\NoteData;
use App\Enums\AutomationActionType;
use App\Enums\AutomationField;
use App\Enums\AutomationMatch;
use App\Enums\AutomationOperator;
use App\Enums\FollowUpType;
use App\Enums\LeadStatus;
use App\Models\Automation;
use App\Models\Lead;
use App\Notifications\AutomationNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class RunAutomation
{
    public function __construct(
        private CreateFollowUp $createFollowUp,
        private UpdateLeadStatus $updateLeadStatus,
        private CreateNote $createNote,
    ) {}

    /**
     * Runs the automation's actions for the lead when its conditions hold, and records the run.
     * A lead that became a client is left alone.
     *
     * @return bool whether it ran
     */
    public function handle(Automation $automation, Lead $lead): bool
    {
        if ($lead->status === LeadStatus::Converted || ! $this->matches($automation, $lead)) {
            return false;
        }

        DB::transaction(function () use ($automation, $lead) {
            foreach ($automation->actions as $action) {
                $this->perform($automation, $lead, $action);
            }

            $automation->runs()->create(['lead_id' => $lead->id]);
        });

        return true;
    }

    private function matches(Automation $automation, Lead $lead): bool
    {
        if ($automation->conditions === []) {
            return true;
        }

        $results = array_map(fn (array $condition) => AutomationField::from($condition['field'])->matches(
            $lead,
            AutomationOperator::from($condition['operator']),
            $condition['value'] ?? null,
        ), $automation->conditions);

        return $automation->match === AutomationMatch::All ? ! in_array(false, $results, true) : in_array(true, $results, true);
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function perform(Automation $automation, Lead $lead, array $action): void
    {
        match (AutomationActionType::from($action['type'])) {
            AutomationActionType::AddFollowUp => $this->createFollowUp->handle($lead, new FollowUpData(
                FollowUpType::from($action['follow_up_type']),
                $action['title'],
                now()->toImmutable()->addHours((int) $action['due_in_hours']),
            )),
            AutomationActionType::MoveToStage => $lead->status->value === $action['stage']
                ? null
                : $this->updateLeadStatus->handle($lead, LeadStatus::from($action['stage']), null, $automation->id),
            AutomationActionType::AddNote => $automation->user
                ? $this->createNote->handle($automation->workspace, new NoteData($action['body'], leadId: $lead->id), $automation->user)
                : null,
            AutomationActionType::NotifyTeam => Notification::send(
                $automation->workspace->users,
                new AutomationNotification($automation, $lead, $action['message']),
            ),
        };
    }
}
