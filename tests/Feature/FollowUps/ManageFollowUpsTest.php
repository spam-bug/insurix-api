<?php

declare(strict_types=1);

use App\Enums\FollowUpType;
use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;

it('adds a follow-up to a lead', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/leads/{$lead->id}/follow-ups", [
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

    expect(FollowUp::first()->workspace_id)->toBe(workspaceOf($agent)->id);
});

it('validates a follow-up', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/leads/{$lead->id}/follow-ups", ['type' => 'fax'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type', 'title', 'due_at']);
});

it('edits a follow-up', function () {
    $agent = User::factory()->create();
    $followUp = FollowUp::factory()->for(Lead::factory()->for(workspaceOf($agent)))->create();

    $this->actingAs($agent)
        ->putJson(ws($agent)."/follow-ups/{$followUp->id}", [
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
    $followUp = FollowUp::factory()->for(Lead::factory()->for(workspaceOf($agent)))->create(['type' => FollowUpType::Call, 'title' => 'Call back']);

    $this->actingAs($agent)
        ->postJson(ws($agent)."/follow-ups/{$followUp->id}/complete")
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
    $followUp = FollowUp::factory()->for(Lead::factory()->for(workspaceOf($agent)))->completed()->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/follow-ups/{$followUp->id}/reopen")
        ->assertOk()
        ->assertJsonPath('data.completed_at', null);
});

it('deletes a follow-up', function () {
    $agent = User::factory()->create();
    $followUp = FollowUp::factory()->for(Lead::factory()->for(workspaceOf($agent)))->create();

    $this->actingAs($agent)
        ->deleteJson(ws($agent)."/follow-ups/{$followUp->id}")
        ->assertNoContent();

    expect(FollowUp::count())->toBe(0);
});

it('hides another workspace’s follow-ups and leads', function () {
    $followUp = FollowUp::factory()->create();
    $agent = User::factory()->create();
    $this->actingAs($agent);

    $this->postJson(ws($agent)."/leads/{$followUp->lead->id}/follow-ups", ['type' => 'call', 'title' => 'Mine now', 'due_at' => now()])->assertNotFound();
    $this->putJson(ws($agent)."/follow-ups/{$followUp->id}", ['type' => 'call', 'title' => 'Mine now', 'due_at' => now()])->assertNotFound();
    $this->postJson(ws($agent)."/follow-ups/{$followUp->id}/complete")->assertNotFound();
    $this->deleteJson(ws($agent)."/follow-ups/{$followUp->id}")->assertNotFound();
});

it('puts a scheduled meeting on the follow-ups, replacing an earlier one', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $this->actingAs($agent);

    $this->postJson(ws($agent)."/leads/{$lead->id}/meeting", ['scheduled_at' => now()->addDay()->toIso8601String()])->assertOk();
    $this->postJson(ws($agent)."/leads/{$lead->id}/meeting", ['scheduled_at' => now()->addDays(3)->toIso8601String()])->assertOk();

    $meetings = $lead->followUps()->where('type', FollowUpType::Meeting)->get();
    expect($meetings)->toHaveCount(1)
        ->and($meetings->first()->due_at->toDateString())->toBe(now()->addDays(3)->toDateString())
        ->and($meetings->first()->title)->toBe("Meeting with {$lead->name}");
});

it('keeps a client’s status when a meeting is scheduled', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Converted)->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/leads/{$lead->id}/meeting", ['scheduled_at' => now()->addDay()->toIso8601String()])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'converted');
});

it('adds a follow-up due now for a shareable form submission', function () {
    Carbon::setTestNow('2026-10-17 02:00:00');
    $agent = User::factory()->create();
    contactFormOf($agent);

    $this->postJson('/api/v1/public/forms/form-token/responses', ['answers' => ['name' => 'Gregoria de Jesus', 'phone' => '0917 555 0100']])->assertCreated();

    $followUp = workspaceOf($agent)->followUps()->sole();
    expect($followUp->type)->toBe(FollowUpType::Call)
        ->and($followUp->title)->toBe('Reach out to Gregoria de Jesus: sent your form')
        ->and($followUp->due_at->toDateTimeString())->toBe('2026-10-17 02:00:00');
});

it('adds a follow-up to a client without a lead, and shows it in their history once done', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();

    $id = $this->actingAs($agent)
        ->postJson(ws($agent)."/clients/{$client->id}/follow-ups", ['type' => 'call', 'title' => 'Welcome call', 'due_at' => '2026-10-20T09:30:00+08:00'])
        ->assertCreated()
        ->assertJsonPath('data.client.id', $client->id)
        ->assertJsonPath('data.lead', null)
        ->json('data.id');

    $this->postJson(ws($agent)."/follow-ups/{$id}/complete")->assertOk();

    $this->getJson(ws($agent)."/clients/{$client->id}/history")
        ->assertJsonPath('data.0.source', 'follow_up')
        ->assertJsonPath('data.0.follow_up.title', 'Welcome call');
});

it('links a promoted client’s new follow-ups to their lead as well', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $client = Client::factory()->for(workspaceOf($agent))->create(['lead_id' => $lead->id]);

    $this->actingAs($agent)
        ->postJson(ws($agent)."/clients/{$client->id}/follow-ups", ['type' => 'call', 'title' => 'Check in', 'due_at' => '2026-10-20T09:30:00+08:00'])
        ->assertCreated()
        ->assertJsonPath('data.lead.id', $lead->id);
});

it('forbids adding a follow-up to another workspace’s client', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf(User::factory()->create()))->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/clients/{$client->id}/follow-ups", ['type' => 'call', 'title' => 'Nope', 'due_at' => '2026-10-20T09:30:00+08:00'])
        ->assertNotFound();
});
