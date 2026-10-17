<?php

use App\Enums\PaymentFrequency;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

/**
 * @return array<string, mixed>
 */
function renewalPayload(array $overrides = []): array
{
    return [
        'product_name' => 'Sun FitandWell',
        'type' => 'health',
        'status' => 'in_force',
        'premium' => '1500',
        'payment_frequency' => 'monthly',
        'commission_rate' => '30',
        'effective_date' => '2026-01-31',
        ...$overrides,
    ];
}

it('works out the next renewal from the effective date for set terms', function (string $term, string $expected) {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.clients.policies.store', $client), renewalPayload(['renewal_term' => $term, 'renewal_date' => '2030-01-01']))
        ->assertCreated()
        ->assertJsonPath('data.renewal_term.value', $term)
        ->assertJsonPath('data.renewal_date', $expected);

    expect(Policy::sole()->getRawOriginal('renewal_date'))->toBeNull();
})->with([
    'monthly' => ['monthly', '2026-10-31'],
    'quarterly' => ['quarterly', '2026-10-31'],
    'annually' => ['annual', '2027-01-31'],
]);

it('keeps a custom renewal date as entered', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.clients.policies.store', $client), renewalPayload(['renewal_term' => 'custom', 'renewal_date' => '2027-06-15']))
        ->assertCreated()
        ->assertJsonPath('data.renewal_term', ['value' => 'custom', 'label' => 'Custom date'])
        ->assertJsonPath('data.renewal_date', '2027-06-15');
});

it('allows a policy with no renewal', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.clients.policies.store', $client), renewalPayload(['effective_date' => null]))
        ->assertCreated()
        ->assertJsonPath('data.renewal_term', null)
        ->assertJsonPath('data.renewal_date', null);
});

it('needs the date for a custom term and the effective date for a set one', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
    $this->actingAs($agent);

    $this->postJson(route('api.v1.clients.policies.store', $client), renewalPayload(['renewal_term' => 'custom']))
        ->assertJsonValidationErrors(['renewal_date' => 'Pick the date it renews.']);

    $this->postJson(route('api.v1.clients.policies.store', $client), renewalPayload(['renewal_term' => 'quarterly', 'effective_date' => null]))
        ->assertJsonValidationErrors(['effective_date' => 'effective date']);

    $this->postJson(route('api.v1.clients.policies.store', $client), renewalPayload(['renewal_term' => 'yearly']))
        ->assertJsonValidationErrors('renewal_term');
});

it('flags a monthly renewal only in its last week', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
    $quietPremium = ['payment_frequency' => PaymentFrequency::Single];
    $soon = Policy::factory()->for($client)->create([...$quietPremium, 'renewal_term' => RenewalTerm::Monthly, 'effective_date' => '2026-09-20', 'renewal_date' => null]);
    Policy::factory()->for($client)->create([...$quietPremium, 'renewal_term' => RenewalTerm::Monthly, 'effective_date' => '2026-09-30', 'renewal_date' => null]);

    $this->actingAs($agent)
        ->getJson(route('api.v1.policies.attention'))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.policy.id', $soon->id)
        ->assertJsonPath('data.0.reasons.0', ['code' => 'renewal_due', 'label' => 'Renewal coming up', 'date' => '2026-10-20']);
});
