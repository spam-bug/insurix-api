<?php

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;

it('lists a lead’s activity newest first, with who did it', function () {
    $agent = User::factory()->create(['name' => 'Jane Agent']);
    $lead = Lead::factory()->for($agent)->create();
    LeadActivity::factory()->for($lead)->create(['type' => LeadActivityType::Created, 'body' => null, 'created_at' => now()->subHour()]);
    $note = LeadActivity::factory()->for($lead)->create(['body' => 'Called, no answer.']);
    LeadActivity::factory()->create();

    $this->actingAs($agent)
        ->getJson(route('api.v1.leads.activities.index', $lead))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $note->id)
        ->assertJsonPath('data.0.type', 'note')
        ->assertJsonPath('data.0.body', 'Called, no answer.')
        ->assertJsonPath('data.0.actor', ['id' => $agent->id, 'name' => 'Jane Agent'])
        ->assertJsonPath('data.1.type', 'created');
});

it('adds a note to a lead', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.notes.store', $lead), ['body' => '  Prefers WhatsApp.  '])
        ->assertCreated()
        ->assertJsonPath('data.type', 'note')
        ->assertJsonPath('data.body', 'Prefers WhatsApp.');

    expect($lead->activities()->sole())->type->toBe(LeadActivityType::Note)->user_id->toBe($agent->id);
});

it('requires a note body', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.notes.store', $lead), ['body' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('body');
});

it('schedules a meeting and moves the lead to meeting scheduled', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->status(LeadStatus::Contacted)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.meeting.store', $lead), [
            'scheduled_at' => '2030-03-04T09:30:00+08:00',
            'notes' => 'Coffee at Ayala Triangle.',
        ])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'meeting_scheduled')
        ->assertJsonPath('data.meeting_at', '2030-03-04T01:30:00+00:00');

    expect($lead->activities()->sole())
        ->type->toBe(LeadActivityType::MeetingScheduled)
        ->body->toBe('Coffee at Ayala Triangle.')
        ->properties->toBe(['scheduled_at' => '2030-03-04T01:30:00+00:00', 'previous_status' => 'contacted']);
});

it('rejects meetings in the past', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.meeting.store', $lead), ['scheduled_at' => now()->subMinute()->toIso8601String()])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['scheduled_at' => 'Choose a time in the future.']);

    expect($lead->fresh()->meeting_at)->toBeNull();
});

it('keeps another agent’s lead activity private', function (string $method, string $routeName, array $payload) {
    $lead = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->json($method, route($routeName, $lead), $payload)
        ->assertForbidden();

    expect($lead->activities()->count())->toBe(0);
})->with([
    'list' => ['GET', 'api.v1.leads.activities.index', []],
    'note' => ['POST', 'api.v1.leads.notes.store', ['body' => 'Hi']],
    'meeting' => ['POST', 'api.v1.leads.meeting.store', ['scheduled_at' => '2030-01-01T10:00:00Z']],
]);
