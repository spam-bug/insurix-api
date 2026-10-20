<?php

declare(strict_types=1);

use App\Enums\LeadStatus;
use App\Models\Client;
use App\Models\Document;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

it('puts lead activity, policy changes and documents in one timeline, newest first', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->status(LeadStatus::Converted)->create();
    $client = Client::factory()->for($agent)->create(['lead_id' => $lead->id]);
    $note = LeadActivity::factory()->for($lead)->create(['body' => 'Wants cover for the kids', 'created_at' => now()->subDays(3)]);
    $this->actingAs($agent);

    Carbon::setTestNow(now()->subDays(2));
    $this->postJson("/api/v1/clients/{$client->id}/policies", [
        'product_name' => 'Life Plus', 'type' => 'life', 'status' => 'pending', 'premium' => 2500,
        'payment_frequency' => 'monthly', 'commission_rate' => 30,
    ])->assertCreated();
    $policyId = $client->policies()->sole()->id;

    Carbon::setTestNow(now()->addDay());
    $document = Document::factory()->for($client)->create(['name' => 'Valid ID', 'policy_id' => $policyId]);

    Carbon::setTestNow(now()->addDay());
    $this->postJson("/api/v1/policies/{$policyId}/approve", ['effective_date' => '2026-10-20'])->assertOk();

    $history = $this->getJson("/api/v1/clients/{$client->id}/history")->assertOk()->json('data');

    expect(array_column($history, 'id'))->toHaveCount(4)
        ->and(array_column($history, 'source'))->toBe(['policy', 'document', 'policy', 'lead'])
        ->and(array_column($history, 'type'))->toBe(['approved', 'uploaded', 'recorded', 'note'])
        ->and($history[0]['policy'])->toBe(['id' => $policyId, 'product_name' => 'Life Plus'])
        ->and($history[1]['document'])->toBe(['id' => $document->id, 'name' => 'Valid ID', 'category' => 'Other'])
        ->and($history[1]['policy']['id'])->toBe($policyId)
        ->and($history[3]['id'])->toBe("lead-{$note->id}")
        ->and($history[3]['body'])->toBe('Wants cover for the kids');
});

it('has a history for clients whose lead was removed', function () {
    $client = Client::factory()->create(['lead_id' => null]);
    Document::factory()->for($client)->create();

    $this->actingAs($client->user)
        ->getJson("/api/v1/clients/{$client->id}/history")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.source', 'document');
});

it('keeps other agents out of a client’s history', function () {
    $this->actingAs(User::factory()->create())
        ->getJson('/api/v1/clients/'.Client::factory()->create()->id.'/history')
        ->assertForbidden();
});
