<?php

use App\Enums\LeadActivityType;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

function validLeadPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Ana Reyes',
        'email' => 'Ana@Example.com',
        'phone' => '+63 917 555 0100',
        'address' => 'Makati City',
        'source' => 'referral',
        'interest' => 'life',
        'notes' => 'Wants a quote for her family.',
    ], $overrides);
}

it('creates a lead as a prospect owned by the agent', function () {
    $agent = User::factory()->create();

    $response = $this->actingAs($agent)
        ->postJson(route('api.v1.leads.store'), validLeadPayload(['status' => 'qualified']))
        ->assertCreated()
        ->assertJsonPath('data.name', 'Ana Reyes')
        ->assertJsonPath('data.email', 'ana@example.com')
        ->assertJsonPath('data.status', ['value' => 'prospect', 'label' => 'Prospect'])
        ->assertJsonPath('data.source', ['value' => 'referral', 'label' => 'Referral']);

    $lead = Lead::findOrFail($response->json('data.id'));
    expect($lead->user_id)->toBe($agent->id)
        ->and($lead->status)->toBe(LeadStatus::Prospect)
        ->and($lead->activities()->sole())
        ->type->toBe(LeadActivityType::Created)
        ->user_id->toBe($agent->id)
        ->properties->toBe(['source' => 'referral']);
});

it('logs which fields an edit changed, and nothing for a no-op edit', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create(validLeadPayload(['email' => 'ana@example.com']));

    $this->actingAs($agent)->putJson(route('api.v1.leads.update', $lead), validLeadPayload())->assertOk();
    expect($lead->activities()->count())->toBe(0);

    $this->putJson(route('api.v1.leads.update', $lead), validLeadPayload(['name' => 'Ana R. Santos', 'notes' => null]))->assertOk();
    expect($lead->activities()->sole())
        ->type->toBe(LeadActivityType::Updated)
        ->properties->toBe(['fields' => ['name', 'notes']]);
});

it('validates lead details', function (array $overrides, string|array $invalidFields) {
    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.leads.store'), validLeadPayload($overrides))
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalidFields);

    expect(Lead::count())->toBe(0);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'no way to contact' => [['email' => null, 'phone' => null], ['email', 'phone']],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'unknown source' => [['source' => 'billboard'], 'source'],
    'unknown interest' => [['interest' => 'pet'], 'interest'],
]);

it('creates a lead from just a name and contact details', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.leads.store'), ['name' => 'Ben Cruz', 'email' => 'ben@example.com', 'phone' => '0917 555 0100'])
        ->assertCreated()
        ->assertJsonPath('data.source', ['value' => 'manual', 'label' => 'Manual entry'])
        ->assertJsonPath('data.interest', null)
        ->assertJsonPath('data.status.value', 'prospect');
});

it('requires the source when editing, so it is never reset by accident', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create(['source' => 'shareable_form']);

    $this->actingAs($agent)
        ->putJson(route('api.v1.leads.update', $lead), validLeadPayload(['source' => null]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('source');

    expect($lead->fresh()->source->value)->toBe('shareable_form');
});

it('lets an edit clear the interest', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create(['interest' => 'life']);

    $this->actingAs($agent)
        ->putJson(route('api.v1.leads.update', $lead), validLeadPayload(['interest' => null]))
        ->assertOk()
        ->assertJsonPath('data.interest', null);
});

it('accepts a lead with only a phone number', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.leads.store'), validLeadPayload(['email' => null]))
        ->assertCreated()
        ->assertJsonPath('data.email', null);
});

it('shows, updates and deletes the agent’s own lead', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->status(LeadStatus::Contacted)->create();

    $this->actingAs($agent)->getJson(route('api.v1.leads.show', $lead))
        ->assertOk()
        ->assertJsonPath('data.id', $lead->id);

    $this->putJson(route('api.v1.leads.update', $lead), validLeadPayload(['name' => 'Ana R. Santos']))
        ->assertOk()
        ->assertJsonPath('data.name', 'Ana R. Santos')
        ->assertJsonPath('data.status.value', 'contacted');

    $this->deleteJson(route('api.v1.leads.destroy', $lead))->assertNoContent();

    $this->assertModelMissing($lead);
});

it('changes a lead’s status', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->patchJson(route('api.v1.leads.status', $lead), ['status' => 'meeting_scheduled'])
        ->assertOk()
        ->assertJsonPath('data.status', ['value' => 'meeting_scheduled', 'label' => 'Meeting scheduled']);

    $this->patchJson(route('api.v1.leads.status', $lead), ['status' => 'client'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    $this->patchJson(route('api.v1.leads.status', $lead), ['status' => 'meeting_scheduled'])->assertOk();

    expect($lead->fresh()->status)->toBe(LeadStatus::MeetingScheduled)
        ->and($lead->activities()->sole())
        ->type->toBe(LeadActivityType::StatusChanged)
        ->properties->toBe(['from' => 'prospect', 'to' => 'meeting_scheduled']);
});

it('forbids access to another agent’s lead', function (string $method, string $routeName, array $payload) {
    $lead = Lead::factory()->create(['name' => 'Original']);

    $this->actingAs(User::factory()->create())
        ->json($method, route($routeName, $lead), $payload)
        ->assertForbidden();

    expect($lead->fresh())->not->toBeNull()
        ->name->toBe('Original')
        ->status->toBe(LeadStatus::Prospect);
})->with([
    'show' => ['GET', 'api.v1.leads.show', []],
    'update' => ['PUT', 'api.v1.leads.update', validLeadPayload()],
    'status' => ['PATCH', 'api.v1.leads.status', ['status' => 'lost']],
    'delete' => ['DELETE', 'api.v1.leads.destroy', []],
]);
