<?php

use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * @return array<string, mixed>
 */
function policyPayload(array $overrides = []): array
{
    return [
        'insurer' => 'Sun Life',
        'product_name' => 'Sun MaxiLink Prime',
        'policy_number' => 'SL-0012345',
        'type' => 'life',
        'status' => 'in_force',
        'premium' => '2500.50',
        'payment_frequency' => 'monthly',
        'commission_rate' => '30',
        'effective_date' => '2026-07-15',
        'renewal_date' => '2027-07-15',
        ...$overrides,
    ];
}

it('records a policy for a client, with its yearly figures', function () {
    Carbon::setTestNow('2026-10-17');
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.clients.policies.store', $client), policyPayload())
        ->assertCreated()
        ->assertJsonPath('data.client', ['id' => $client->id, 'name' => $client->name])
        ->assertJsonPath('data.status', ['value' => 'in_force', 'label' => 'In force'])
        ->assertJsonPath('data.premium', 2500.5)
        ->assertJsonPath('data.annual_premium', 30006)
        ->assertJsonPath('data.commission', 9001.8)
        ->assertJsonPath('data.next_payment_due', '2026-11-15');

    expect(Policy::sole()->user_id)->toBe($agent->id);
});

it('works out the next premium for each payment frequency', function (string $frequency, ?string $expected) {
    Carbon::setTestNow('2026-10-17');
    $policy = Policy::factory()->create(['payment_frequency' => $frequency, 'effective_date' => '2026-01-31']);

    expect($policy->nextPaymentDue()?->toDateString())->toBe($expected);
})->with([
    'monthly' => ['monthly', '2026-10-31'],
    'quarterly' => ['quarterly', '2026-10-31'],
    'semi-annual' => ['semi_annual', '2027-01-31'],
    'annual' => ['annual', '2027-01-31'],
    'single payment' => ['single', null],
]);

it('has no next premium once a policy is no longer in force', function () {
    $policy = Policy::factory()->status(PolicyStatus::Lapsed)->create();

    expect($policy->nextPaymentDue())->toBeNull();
});

it('validates a policy', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.clients.policies.store', $client), policyPayload([
            'insurer' => '',
            'premium' => '0',
            'commission_rate' => '120',
            'payment_frequency' => 'weekly',
            'renewal_date' => '2026-01-01',
        ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['insurer', 'premium', 'commission_rate', 'payment_frequency', 'renewal_date']);
});

it('lists, filters and searches the agent’s policies', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create(['name' => 'Teodora Alonso']);
    $inForce = Policy::factory()->for($client)->create(['insurer' => 'Pru Life UK']);
    $pending = Policy::factory()->for($client)->status(PolicyStatus::Pending)->create(['insurer' => 'AXA Philippines']);
    Policy::factory()->create();
    $this->actingAs($agent);

    $this->getJson(route('api.v1.policies.index'))->assertJsonCount(2, 'data');
    $this->getJson(route('api.v1.policies.index', ['status' => 'pending']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $pending->id);
    $this->getJson(route('api.v1.policies.index', ['search' => 'pru']))->assertJsonCount(1, 'data')->assertJsonPath('data.0.id', $inForce->id);
    $this->getJson(route('api.v1.policies.index', ['search' => 'teodora']))->assertJsonCount(2, 'data');
    $this->getJson(route('api.v1.policies.index', ['client_id' => $client->id]))->assertJsonCount(2, 'data');
});

it('updates and deletes a policy', function () {
    $agent = User::factory()->create();
    $policy = Policy::factory()->for(Client::factory()->for($agent))->status(PolicyStatus::Pending)->create();
    $this->actingAs($agent);

    $this->putJson(route('api.v1.policies.update', $policy), policyPayload(['commission_received_at' => '2026-10-01']))
        ->assertOk()
        ->assertJsonPath('data.status.value', 'in_force')
        ->assertJsonPath('data.commission_received_at', '2026-10-01');

    $this->deleteJson(route('api.v1.policies.destroy', $policy))->assertNoContent();
    expect(Policy::count())->toBe(0);
});

it('forbids another agent’s clients and policies', function () {
    $policy = Policy::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->postJson(route('api.v1.clients.policies.store', $policy->client), policyPayload())->assertForbidden();
    $this->getJson(route('api.v1.policies.show', $policy))->assertForbidden();
    $this->putJson(route('api.v1.policies.update', $policy), policyPayload())->assertForbidden();
    $this->deleteJson(route('api.v1.policies.destroy', $policy))->assertForbidden();
});
