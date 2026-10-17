<?php

use App\Enums\FollowUpType;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;

it('adds a follow-up to a lead', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.follow-ups.store', $lead), [
            'type' => 'call',
            'title' => '  Call about the quote ',
            'due_at' => '2026-10-20T09:30:00+08:00',
            'notes' => 'Ask about riders.',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', ['value' => 'call', 'label' => 'Call'])
        ->assertJsonPath('data.title', 'Call about the quote')
        ->assertJsonPath('data.due_at', '2026-10-20T01:30:00+00:00')
        ->assertJsonPath('data.completed_at', null)
        ->assertJsonPath('data.lead.id', $lead->id);

    expect(FollowUp::first()->user_id)->toBe($agent->id);
});

it('validates a follow-up', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.follow-ups.store', $lead), ['type' => 'fax'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'title', 'due_at']);
});

it('edits a follow-up', function () {
    $agent = User::factory()->create();
    $followUp = FollowUp::factory()->for(Lead::factory()->for($agent))->create();

    $this->actingAs($agent)
        ->putJson(route('api.v1.follow-ups.update', $followUp), [
            'type' => 'email',
            'title' => 'Send the proposal',
            'due_at' => '2026-10-21T10:00:00Z',
        ])
        ->assertOk()
        ->assertJsonPath('data.type.value', 'email')
        ->assertJsonPath('data.title', 'Send the proposal')
        ->assertJsonPath('data.notes', null);
});

it('completes a follow-up and logs it on the lead', function () {
    $agent = User::factory()->create();
    $followUp = FollowUp::factory()->for(Lead::factory()->for($agent))->create(['type' => FollowUpType::Call, 'title' => 'Call back']);

    $this->actingAs($agent)
        ->postJson(route('api.v1.follow-ups.complete', $followUp))
        ->assertOk()
        ->assertJsonPath('data.is_overdue', false);

    expect($followUp->fresh()->completed_at)->not->toBeNull();

    $activity = $followUp->lead->activities()->latest('id')->first();
    expect($activity->type)->toBe(LeadActivityType::FollowUpCompleted)
        ->and($activity->user_id)->toBe($agent->id)
        ->and($activity->properties)->toMatchArray(['follow_up_type' => 'call', 'title' => 'Call back']);
});

it('reopens a completed follow-up', function () {
    $agent = User::factory()->create();
    $followUp = FollowUp::factory()->for(Lead::factory()->for($agent))->completed()->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.follow-ups.reopen', $followUp))
        ->assertOk()
        ->assertJsonPath('data.completed_at', null);
});

it('deletes a follow-up', function () {
    $agent = User::factory()->create();
    $followUp = FollowUp::factory()->for(Lead::factory()->for($agent))->create();

    $this->actingAs($agent)
        ->deleteJson(route('api.v1.follow-ups.destroy', $followUp))
        ->assertNoContent();

    expect(FollowUp::count())->toBe(0);
});

it('forbids another agent’s follow-ups and leads', function () {
    $followUp = FollowUp::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->postJson(route('api.v1.leads.follow-ups.store', $followUp->lead), ['type' => 'call', 'title' => 'Mine now', 'due_at' => now()])->assertForbidden();
    $this->putJson(route('api.v1.follow-ups.update', $followUp), ['type' => 'call', 'title' => 'Mine now', 'due_at' => now()])->assertForbidden();
    $this->postJson(route('api.v1.follow-ups.complete', $followUp))->assertForbidden();
    $this->deleteJson(route('api.v1.follow-ups.destroy', $followUp))->assertForbidden();
});

it('puts a scheduled meeting on the follow-ups, replacing an earlier one', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();
    $this->actingAs($agent);

    $this->postJson(route('api.v1.leads.meeting.store', $lead), ['scheduled_at' => now()->addDay()->toIso8601String()])->assertOk();
    $this->postJson(route('api.v1.leads.meeting.store', $lead), ['scheduled_at' => now()->addDays(3)->toIso8601String()])->assertOk();

    $meetings = $lead->followUps()->where('type', FollowUpType::Meeting)->get();
    expect($meetings)->toHaveCount(1)
        ->and($meetings->first()->due_at->toDateString())->toBe(now()->addDays(3)->toDateString())
        ->and($meetings->first()->title)->toBe("Meeting with {$lead->name}");
});

it('keeps a client’s status when a meeting is scheduled', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->status(LeadStatus::Converted)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.meeting.store', $lead), ['scheduled_at' => now()->addDay()->toIso8601String()])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'converted');
});

it('adds a follow-up due now for a shareable form submission', function () {
    Carbon::setTestNow('2026-10-17 02:00:00');
    $agent = User::factory()->create(['lead_form_token' => 'form-token']);

    $this->postJson(route('api.v1.public.lead-forms.submit', 'form-token'), ['name' => 'Gregoria de Jesus', 'phone' => '0917 555 0100'])->assertCreated();

    $followUp = $agent->followUps()->sole();
    expect($followUp->type)->toBe(FollowUpType::Call)
        ->and($followUp->title)->toBe('Reach out to Gregoria de Jesus: sent your form')
        ->and($followUp->due_at->toDateTimeString())->toBe('2026-10-17 02:00:00');
});
