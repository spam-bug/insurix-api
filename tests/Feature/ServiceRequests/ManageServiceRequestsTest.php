<?php

declare(strict_types=1);

use App\Enums\ServiceRequestStatus;
use App\Models\Client;
use App\Models\Policy;
use App\Models\ServiceRequest;
use App\Models\User;

it('logs a claim for a client with its amount and reference', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $policy = Policy::factory()->for($client)->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/clients/{$client->id}/service-requests", [
            'type' => 'claim',
            'title' => 'Hospital bill for March',
            'policy_id' => $policy->id,
            'due_on' => '2026-11-15',
            'claim_amount' => '48500.50',
            'insurer_reference' => 'CLM-2026-0042',
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', ['value' => 'claim', 'label' => 'Claim'])
        ->assertJsonPath('data.status', ['value' => 'open', 'label' => 'Open'])
        ->assertJsonPath('data.claim_amount', 48500.5)
        ->assertJsonPath('data.insurer_reference', 'CLM-2026-0042')
        ->assertJsonPath('data.policy.id', $policy->id)
        ->assertJsonPath('data.client.id', $client->id)
        ->assertJsonPath('data.author.id', $agent->id);
});

it('drops claim details from other kinds of request', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/clients/{$client->id}/service-requests", [
            'type' => 'inquiry',
            'title' => 'Asks about adding a rider',
            'claim_amount' => 1000,
            'insurer_reference' => 'X',
        ])
        ->assertCreated()
        ->assertJsonPath('data.claim_amount', null)
        ->assertJsonPath('data.insurer_reference', null);
});

it('validates a request', function (array $payload, array $invalid) {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $otherPolicy = Policy::factory()->create();

    $this->actingAs($agent)
        ->postJson(ws($agent)."/clients/{$client->id}/service-requests", [
            'type' => 'inquiry',
            'title' => 'A question',
            ...$payload,
            ...(isset($payload['policy_id']) ? ['policy_id' => $otherPolicy->id] : []),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors($invalid);
})->with([
    'missing title' => [['title' => ''], ['title']],
    'unknown type' => [['type' => 'complaint'], ['type']],
    'another client’s policy' => [['policy_id' => 'set below'], ['policy_id']],
    'negative claim' => [['type' => 'claim', 'claim_amount' => -5], ['claim_amount']],
]);

it('lists requests by view, most urgent first', function () {
    $this->travelTo('2026-11-10 09:00:00');
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $later = ServiceRequest::factory()->for($client)->create(['due_on' => '2026-11-20']);
    $overdue = ServiceRequest::factory()->for($client)->create(['due_on' => '2026-11-01']);
    $undated = ServiceRequest::factory()->for($client)->create();
    $waiting = ServiceRequest::factory()->for($client)->create(['status' => ServiceRequestStatus::WaitingOnInsurer, 'due_on' => '2026-11-12']);
    $resolved = ServiceRequest::factory()->for($client)->resolved()->create(['due_on' => '2026-10-01']);

    $this->actingAs($agent);

    expect($this->getJson(ws($agent).'/service-requests')->json('data.*.id'))->toBe([$overdue->id, $waiting->id, $later->id, $undated->id])
        ->and($this->getJson(ws($agent).'/service-requests?view=overdue')->json('data.*.id'))->toBe([$overdue->id])
        ->and($this->getJson(ws($agent).'/service-requests?view=waiting_on_insurer')->json('data.*.id'))->toBe([$waiting->id])
        ->and($this->getJson(ws($agent).'/service-requests?view=resolved')->json('data.*.id'))->toBe([$resolved->id])
        ->and($this->getJson(ws($agent).'/service-requests/summary')->json('data'))->toBe(['unresolved' => 4, 'overdue' => 1, 'waiting_on_insurer' => 1]);
});

it('lists one client’s requests', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $mine = ServiceRequest::factory()->for($client)->create();
    ServiceRequest::factory()->for(Client::factory()->for(workspaceOf($agent)))->create();

    $this->actingAs($agent)
        ->getJson(ws($agent)."/service-requests?client_id={$client->id}")
        ->assertJsonPath('data.*.id', [$mine->id]);
});

it('stamps when a request was resolved and clears it when reopened', function () {
    $agent = User::factory()->create();
    $serviceRequest = ServiceRequest::factory()->for(Client::factory()->for(workspaceOf($agent)))->create();

    $this->actingAs($agent)
        ->patchJson(ws($agent)."/service-requests/{$serviceRequest->id}/status", ['status' => 'resolved'])
        ->assertOk()
        ->assertJsonPath('data.status.value', 'resolved');
    $resolvedAt = $serviceRequest->fresh()->resolved_at;
    expect($resolvedAt)->not->toBeNull();

    $this->travel(1)->day();
    $this->putJson(ws($agent)."/service-requests/{$serviceRequest->id}", ['type' => 'inquiry', 'title' => 'Renamed', 'status' => 'resolved'])->assertOk();
    expect($serviceRequest->fresh()->resolved_at->equalTo($resolvedAt))->toBeTrue();

    $this->patchJson(ws($agent)."/service-requests/{$serviceRequest->id}/status", ['status' => 'in_progress'])->assertOk();
    expect($serviceRequest->fresh()->resolved_at)->toBeNull();
});

it('deletes a request', function () {
    $agent = User::factory()->create();
    $serviceRequest = ServiceRequest::factory()->for(Client::factory()->for(workspaceOf($agent)))->create();

    $this->actingAs($agent)->deleteJson(ws($agent)."/service-requests/{$serviceRequest->id}")->assertNoContent();

    expect(ServiceRequest::count())->toBe(0);
});

it('keeps other workspaces’ requests out of reach', function () {
    $agent = User::factory()->create();
    $serviceRequest = ServiceRequest::factory()->create();
    $client = $serviceRequest->client;

    $this->actingAs($agent);
    $this->postJson(ws($agent)."/clients/{$client->id}/service-requests", ['type' => 'inquiry', 'title' => 'Hi'])->assertNotFound();
    $this->putJson(ws($agent)."/service-requests/{$serviceRequest->id}", ['type' => 'inquiry', 'title' => 'Hi', 'status' => 'open'])->assertNotFound();
    $this->patchJson(ws($agent)."/service-requests/{$serviceRequest->id}/status", ['status' => 'resolved'])->assertNotFound();
    $this->deleteJson(ws($agent)."/service-requests/{$serviceRequest->id}")->assertNotFound();
    expect($this->getJson(ws($agent).'/service-requests')->json('data'))->toBe([]);
});

it('shows opened and resolved requests in the client’s history', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    ServiceRequest::factory()->for($client)->resolved()->create(['title' => 'Change beneficiary', 'created_at' => now()->subDay()]);

    $history = $this->actingAs($agent)->getJson(ws($agent)."/clients/{$client->id}/history")->json('data');

    expect(collect($history)->where('source', 'request')->pluck('type')->all())->toBe(['resolved', 'opened'])
        ->and($history[0]['request']['title'])->toBe('Change beneficiary');
});
