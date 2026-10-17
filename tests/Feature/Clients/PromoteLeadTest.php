<?php

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Lead;
use App\Models\User;

it('promotes a lead to a client, carrying its details over', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->status(LeadStatus::Qualified)->create(['notes' => 'Wants family cover.']);

    $response = $this->actingAs($agent)
        ->postJson(route('api.v1.leads.promote', $lead), [
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
    expect($client->user_id)->toBe($agent->id)
        ->and($lead->fresh()->status)->toBe(LeadStatus::Converted);

    $activity = $lead->activities()->latest('id')->first();
    expect($activity->type)->toBe(LeadActivityType::Promoted)
        ->and($activity->properties)->toBe(['client_id' => $client->id, 'previous_status' => 'qualified']);
});

it('lets the client-only details be left out', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.promote', $lead))
        ->assertCreated()
        ->assertJsonPath('data.birthdate', null)
        ->assertJsonPath('data.civil_status', null);
});

it('shows the client on the promoted lead', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();
    $clientId = $this->actingAs($agent)->postJson(route('api.v1.leads.promote', $lead))->json('data.id');

    $this->getJson(route('api.v1.leads.show', $lead))->assertJsonPath('data.client_id', $clientId);
    $this->getJson(route('api.v1.leads.index'))->assertJsonPath('data.0.client_id', $clientId);
});

it('promotes a lead only once', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();
    $this->actingAs($agent)->postJson(route('api.v1.leads.promote', $lead))->assertCreated();

    $this->postJson(route('api.v1.leads.promote', $lead))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('lead');

    expect(Client::count())->toBe(1);
});

it('validates the client details', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.leads.promote', $lead), ['birthdate' => now()->addDay()->toDateString(), 'civil_status' => 'complicated'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['birthdate', 'civil_status']);
});

it('forbids promoting another agent’s lead', function () {
    $lead = Lead::factory()->create();

    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.leads.promote', $lead))
        ->assertForbidden();

    expect(Client::count())->toBe(0);
});

it('keeps converted out of manual status changes', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->patchJson(route('api.v1.leads.status', $lead), ['status' => 'converted'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    $this->postJson(route('api.v1.leads.bulk.status'), ['ids' => [$lead->id], 'status' => 'converted'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('locks a promoted lead’s status', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->status(LeadStatus::Converted)->create();

    $this->actingAs($agent)
        ->patchJson(route('api.v1.leads.status', $lead), ['status' => 'lost'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    $this->postJson(route('api.v1.leads.bulk.status'), ['ids' => [$lead->id], 'status' => 'lost'])->assertOk();

    expect($lead->fresh()->status)->toBe(LeadStatus::Converted);
});

it('counts converted leads', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->status(LeadStatus::Converted)->create();

    $this->actingAs($agent)
        ->getJson(route('api.v1.leads.status-counts'))
        ->assertJsonPath('data.converted', 1)
        ->assertJsonPath('data.total', 1);
});
