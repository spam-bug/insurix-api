<?php

declare(strict_types=1);

use App\Enums\AutomationTrigger;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\Plan;
use App\Models\Automation;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Note;
use App\Models\User;
use App\Notifications\AutomationNotification;
use Illuminate\Support\Facades\Notification;

function premiumAgent(): User
{
    $agent = User::factory()->create();
    workspaceOf($agent)->forceFill(['plan' => Plan::Premium])->save();

    return $agent;
}

function automationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Call form leads fast',
        'trigger' => 'lead_created',
        'match' => 'all',
        'conditions' => [['field' => 'source', 'operator' => 'is', 'value' => 'shareable_form']],
        'actions' => [['type' => 'add_follow_up', 'follow_up_type' => 'call', 'title' => 'Call them back', 'due_in_hours' => 1]],
    ], $overrides);
}

it('lets a Premium workspace save an automation, and no one else', function () {
    $agent = premiumAgent();

    $this->actingAs($agent)
        ->postJson(ws($agent).'/automations', automationPayload())
        ->assertCreated()
        ->assertJsonPath('data.trigger.value', 'lead_created')
        ->assertJsonPath('data.conditions.0.field', 'source')
        ->assertJsonPath('data.conditions.0.value', 'shareable_form')
        ->assertJsonPath('data.enabled', true);

    $free = User::factory()->create();
    $this->actingAs($free)->postJson(ws($free).'/automations', automationPayload())->assertForbidden();
    $this->getJson(ws($free).'/automations')->assertForbidden();
});

it('validates each condition and action for its own kind', function (array $overrides, array $invalid) {
    $agent = premiumAgent();

    $this->actingAs($agent)
        ->postJson(ws($agent).'/automations', automationPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalid);
})->with([
    'operator that doesn’t suit the field' => [['conditions' => [['field' => 'score', 'operator' => 'is', 'value' => 50]]], ['conditions.0.operator']],
    'unknown source' => [['conditions' => [['field' => 'source', 'operator' => 'is', 'value' => 'billboard']]], ['conditions.0.value']],
    'no actions' => [['actions' => []], ['actions']],
    'follow-up without a title' => [['actions' => [['type' => 'add_follow_up', 'follow_up_type' => 'call', 'due_in_hours' => 1]]], ['actions.0.title']],
    'moving to Client' => [['actions' => [['type' => 'move_to_stage', 'stage' => 'converted']]], ['actions.0.stage']],
    'quiet days missing' => [['trigger' => 'lead_idle'], ['idle_days']],
]);

it('runs on a new lead that matches its conditions', function () {
    $agent = premiumAgent();
    $automation = Automation::factory()->for(workspaceOf($agent))->create(automationPayload());

    $this->actingAs($agent)->postJson(ws($agent).'/leads', ['name' => 'Walk-in', 'phone' => '0917 555 0100', 'source' => 'manual'])->assertCreated();
    expect(FollowUp::count())->toBe(0);

    $this->postJson(ws($agent).'/leads', ['name' => 'From the form', 'phone' => '0917 555 0101', 'source' => 'shareable_form'])->assertCreated();
    $lead = Lead::where('source', LeadSource::ShareableForm)->sole();

    $followUp = FollowUp::sole();
    expect($followUp->lead_id)->toBe($lead->id)
        ->and($followUp->title)->toBe('Call them back')
        ->and($followUp->due_at->diffInMinutes(now()->addHour()))->toBeLessThan(1)
        ->and($automation->runs()->sole()->lead_id)->toBe($lead->id);
});

it('runs when a lead moves to its stage, adding a note and telling the team', function () {
    Notification::fake();
    $agent = premiumAgent();
    Automation::factory()->for(workspaceOf($agent))->for($agent)->create([
        'name' => 'Qualified leads',
        'trigger' => AutomationTrigger::LeadStageChanged,
        'trigger_stage' => LeadStatus::Qualified,
        'actions' => [
            ['type' => 'add_note', 'body' => 'Send the proposal this week.'],
            ['type' => 'notify_team', 'message' => 'is ready for a proposal'],
        ],
    ]);
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)->patchJson(ws($agent)."/leads/{$lead->id}/status", ['status' => 'contacted'])->assertOk();
    expect(Note::count())->toBe(0);

    $this->patchJson(ws($agent)."/leads/{$lead->id}/status", ['status' => 'qualified'])->assertOk();

    expect(Note::sole())->body->toBe('Send the proposal this week.')->lead_id->toBe($lead->id)->user_id->toBe($agent->id);
    Notification::assertSentTo($agent, AutomationNotification::class, fn (AutomationNotification $notification) => $notification->toArray($agent)['body'] === "{$lead->name}: is ready for a proposal");
});

it('doesn’t let one automation’s stage change set off another', function () {
    $agent = premiumAgent();
    $workspace = workspaceOf($agent);
    Automation::factory()->for($workspace)->create(['actions' => [['type' => 'move_to_stage', 'stage' => 'contacted']]]);
    $watcher = Automation::factory()->for($workspace)->create(['trigger' => AutomationTrigger::LeadStageChanged, 'actions' => [['type' => 'move_to_stage', 'stage' => 'qualified']]]);

    $this->actingAs($agent)->postJson(ws($agent).'/leads', ['name' => 'Ana', 'phone' => '0917 555 0100'])->assertCreated();

    expect(Lead::sole()->status)->toBe(LeadStatus::Contacted)
        ->and($watcher->runs()->count())->toBe(0);
});

it('runs once per quiet spell for leads with no activity', function () {
    $agent = premiumAgent();
    $automation = Automation::factory()->for(workspaceOf($agent))->create(['trigger' => AutomationTrigger::LeadIdle, 'idle_days' => 3]);
    $this->actingAs($agent)->postJson(ws($agent).'/leads', ['name' => 'Quiet', 'phone' => '0917 555 0100'])->assertCreated();
    $lead = Lead::sole();

    $this->travel(2)->days();
    $this->artisan('automations:run-idle')->assertSuccessful();
    expect($automation->runs()->count())->toBe(0);

    $this->travel(2)->days();
    $this->artisan('automations:run-idle');
    $this->artisan('automations:run-idle');
    expect($automation->runs()->count())->toBe(1);

    $this->travel(1)->hour();
    $this->patchJson(ws($agent)."/leads/{$lead->id}/status", ['status' => 'contacted'])->assertOk();
    $this->travel(2)->days();
    $this->artisan('automations:run-idle');
    expect($automation->runs()->count())->toBe(1);

    $this->travel(2)->days();
    $this->artisan('automations:run-idle');
    expect($automation->runs()->count())->toBe(2);
});

it('does nothing when turned off or when the workspace isn’t Premium', function () {
    $agent = premiumAgent();
    $automation = Automation::factory()->for(workspaceOf($agent))->create(['conditions' => []]);

    $this->actingAs($agent)->putJson(ws($agent)."/automations/{$automation->id}/enabled", ['enabled' => false])->assertOk()->assertJsonPath('data.enabled', false);
    $this->postJson(ws($agent).'/leads', ['name' => 'Off', 'phone' => '0917 555 0100'])->assertCreated();

    $automation->update(['enabled' => true]);
    workspaceOf($agent)->forceFill(['plan' => Plan::Free])->save();
    $this->postJson(ws($agent).'/leads', ['name' => 'Free', 'phone' => '0917 555 0101'])->assertCreated();

    expect($automation->runs()->count())->toBe(0)->and(FollowUp::count())->toBe(0);
});

it('lists automations with how often they ran, and deletes them', function () {
    $agent = premiumAgent();
    $automation = Automation::factory()->for(workspaceOf($agent))->create(['conditions' => []]);
    $this->actingAs($agent)->postJson(ws($agent).'/leads', ['name' => 'Ana', 'phone' => '0917 555 0100'])->assertCreated();

    $this->getJson(ws($agent).'/automations')
        ->assertOk()
        ->assertJsonPath('data.0.runs_count', 1)
        ->assertJsonPath('data.0.last_run_at', fn (?string $value) => $value !== null);

    $this->deleteJson(ws($agent)."/automations/{$automation->id}")->assertNoContent();
    expect(Automation::count())->toBe(0)->and(FollowUp::count())->toBe(1);
});
