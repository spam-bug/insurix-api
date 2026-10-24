<?php

declare(strict_types=1);

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;

it('promotes a lead to a client, carrying its details over', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Qualified)->create(['notes' => 'Wants family cover.']);

    $response = $this->actingAs($agent)
        ->postJson(ws($agent)."/leads/{$lead->id}/promote", [
            'birthdate' => '1988-04-12',
            'civil_status' => 'married',
            'occupation' => '  Nurse ',
        ])
        ->assertCreated()
        ->assertJsonPath('data.lead_id', $lead->id)
        ->assertJsonPath('data.name', $lead->name)
        ->assertJsonPath('data.email', $lead->email)
        ->assertJsonPath('data.notes', 'Wants family cover.')
        ->assertJsonPath('data.birthdate', '1988-04-12')
        ->assertJsonPath('data.civil_status', ['value' => 'married', 'label' => 'Married'])
        ->assertJsonPath('data.occupation', 'Nurse');

    $client = Client::findOrFail($response->json('data.id'));
    expect($client->workspace_id)->toBe(workspaceOf($agent)->id)
        ->and($lead->fresh()->status)->toBe(LeadStatus::Converted);

    $activity = $lead->activities()->latest('id')->first();
    expect($activity->type)->toBe(LeadActivityType::Promoted)
        ->and($activity->properties)->toBe(['client_id' => $client->id, 'previous_status' => 'qualified']);
});

it('lets the client-only details be left out', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/leads/{$lead->id}/promote")
        ->assertCreated()
        ->assertJsonPath('data.birthdate', null)
        ->assertJsonPath('data.civil_status', null);
});

it('shows the client on the promoted lead', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $clientId = $this->actingAs($agent)->postJson(ws($agent)."/leads/{$lead->id}/promote")->json('data.id');

    $this->getJson(ws($agent)."/leads/{$lead->id}")->assertJsonPath('data.client_id', $clientId);
    $this->getJson(ws($agent).'/leads')->assertJsonPath('data.0.client_id', $clientId);
});

it('promotes a lead only once', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $this->actingAs($agent)->postJson(ws($agent)."/leads/{$lead->id}/promote")->assertCreated();

    $this->postJson(ws($agent)."/leads/{$lead->id}/promote")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lead');

    expect(Client::count())->toBe(1);
});

it('validates the client details', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/leads/{$lead->id}/promote", ['birthdate' => now()->addDay()->toDateString(), 'civil_status' => 'complicated'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['birthdate', 'civil_status']);
});

it('forbids promoting another agent’s lead', function () {
    $lead = Lead::factory()->create();

    $this->actingAs($agent = User::factory()->create())
        ->postJson(ws($agent)."/leads/{$lead->id}/promote")
        ->assertNotFound();

    expect(Client::count())->toBe(0);
});

it('keeps converted out of manual status changes', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)
        ->patchJson(ws($agent)."/leads/{$lead->id}/status", ['status' => 'converted'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    $this->postJson(ws($agent).'/leads/bulk/status', ['ids' => [$lead->id], 'status' => 'converted'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('locks a promoted lead’s status', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Converted)->create();

    $this->actingAs($agent)
        ->patchJson(ws($agent)."/leads/{$lead->id}/status", ['status' => 'lost'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    $this->postJson(ws($agent).'/leads/bulk/status', ['ids' => [$lead->id], 'status' => 'lost'])->assertOk();

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted);
});

it('counts converted leads', function () {
    $agent = User::factory()->create();
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Converted)->create();

    $this->actingAs($agent)
        ->getJson(ws($agent).'/leads/status-counts')
        ->assertJsonPath('data.converted', 1)
        ->assertJsonPath('data.total', 1);
});

it('moves the lead’s follow-ups to the new client', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Qualified)->create();
    $followUp = FollowUp::factory()->for($lead)->create();

    $clientId = $this->actingAs($agent)->postJson(ws($agent)."/leads/{$lead->id}/promote")->assertCreated()->json('data.id');

    expect($followUp->fresh())->client_id->toBe($clientId)->lead_id->toBe($lead->id);
});
