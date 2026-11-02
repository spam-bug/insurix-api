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
use App\Enums\AutomationRunStatus;
use App\Enums\AutomationWaitUnit;
use App\Enums\Feature;
use App\Enums\FollowUpType;
use App\Enums\FormStatus;
use App\Enums\LeadStatus;
use App\Models\Automation;
use App\Models\AutomationRun;
use App\Models\Form;
use App\Models\Lead;
use App\Notifications\AutomationNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Pennant\Feature as Pennant;

class RunAutomation
{
    public function __construct(
        private CreateFollowUp $createFollowUp,
        private UpdateLeadStatus $updateLeadStatus,
        private CreateNote $createNote,
    ) {}

    /**
     * Runs the automation's steps for the lead when its conditions hold, and records the run.
     * At a wait, the run pauses with the steps still to come. A lead that became a client is
     * left alone.
     *
     * @return bool whether it ran
     */
    public function handle(Automation $automation, Lead $lead): bool
    {
        if (! $this->applies($automation, $lead)) {
            return false;
        }

        DB::transaction(fn () => $this->performUntilWait(
            $automation,
            $lead,
            $automation->runs()->make(['lead_id' => $lead->id]),
            $automation->actions,
        ));

        return true;
    }

    /**
     * Carries on a run that waited. It stops instead when the automation was turned off, the
     * workspace left Premium, or the lead no longer matches or became a client.
     */
    public function resume(AutomationRun $run): void
    {
        DB::transaction(function () use ($run) {
            $run = AutomationRun::query()->with(['automation.workspace', 'automation.user', 'lead'])->lockForUpdate()->find($run->id);

            if ($run?->status !== AutomationRunStatus::Waiting) {
                return;
            }

            $automation = $run->automation;

            if (! $automation->enabled
                || Pennant::for($automation->workspace)->inactive(Feature::Automations)
                || ! $this->applies($automation, $run->lead)) {
                $run->update(['status' => AutomationRunStatus::Stopped, 'pending_actions' => null, 'resume_at' => null]);

                return;
            }

            $this->performUntilWait($automation, $run->lead, $run, $run->pending_actions ?? []);
        });
    }

    private function applies(Automation $automation, Lead $lead): bool
    {
        return $lead->status !== LeadStatus::Converted && $this->matches($automation, $lead);
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
     * Performs the steps in order. At a wait, the rest is kept on the run with when to carry on.
     *
     * @param  array<int, array<string, mixed>>  $actions
     */
    private function performUntilWait(Automation $automation, Lead $lead, AutomationRun $run, array $actions): void
    {
        $actions = array_values($actions);

        foreach ($actions as $index => $action) {
            if ($action['type'] === AutomationActionType::Wait->value) {
                $run->fill([
                    'status' => AutomationRunStatus::Waiting,
                    'pending_actions' => array_slice($actions, $index + 1),
                    'resume_at' => now()->add(AutomationWaitUnit::from($action['unit'])->interval((int) $action['amount'])),
                ])->save();

                return;
            }

            $this->perform($automation, $lead, $action);
        }

        $run->fill(['status' => AutomationRunStatus::Completed, 'pending_actions' => null, 'resume_at' => null])->save();
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
                $this->dueAt($action),
            )),
            AutomationActionType::DraftMessage => $this->createFollowUp->handle($lead, new FollowUpData(
                FollowUpType::Message,
                'Send your drafted message',
                $this->dueAt($action),
                message: $this->fillIn($action['message'], $automation, $lead),
            )),
            AutomationActionType::SendForm => $this->sendForm($automation, $lead, $action),
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
            AutomationActionType::Wait => null,
        };
    }

    /**
     * A message follow-up with the form's link. A form that was closed or deleted since the
     * automation was saved has no link to send, so the step is skipped.
     *
     * @param  array<string, mixed>  $action
     */
    private function sendForm(Automation $automation, Lead $lead, array $action): void
    {
        $form = $automation->workspace->forms()->where('status', FormStatus::Published)->find($action['form_id']);

        if (! $form) {
            return;
        }

        $this->createFollowUp->handle($lead, new FollowUpData(
            FollowUpType::Message,
            "Send the form “{$form->name}”",
            $this->dueAt($action),
            message: $this->fillIn($action['message'], $automation, $lead, $form),
        ));
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function dueAt(array $action): CarbonImmutable
    {
        return now()->toImmutable()->addHours((int) $action['due_in_hours']);
    }

    /** Puts the lead's first name, the agent's name and the form's link where the message asks for them. */
    private function fillIn(string $message, Automation $automation, Lead $lead, ?Form $form = null): string
    {
        return strtr($message, [
            '{first_name}' => Str::before(trim($lead->name), ' '),
            '{agent_name}' => $automation->user->name ?? $automation->workspace->name,
            '{form_link}' => $form ? config('app.frontend_url')."/f/{$form->token}" : '',
        ]);
    }
}
