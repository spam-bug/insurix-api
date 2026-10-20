<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

it('flags renewals, premiums due, lapsed and long-pending policies, most urgent first', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
    $quiet = ['payment_frequency' => PaymentFrequency::Annual, 'effective_date' => '2026-03-01', 'renewal_date' => '2027-03-01'];

    $renewing = Policy::factory()->for($client)->create([...$quiet, 'renewal_date' => '2026-11-05']);
    $paymentDue = Policy::factory()->for($client)->create(['payment_frequency' => PaymentFrequency::Monthly, 'effective_date' => '2026-01-20', 'renewal_date' => '2027-01-20']);
    $lapsed = Policy::factory()->for($client)->status(PolicyStatus::Lapsed)->create($quiet);
    $stuck = Policy::factory()->for($client)->status(PolicyStatus::Pending)->create([...$quiet, 'created_at' => now()->subDays(20)]);
    Policy::factory()->for($client)->status(PolicyStatus::Pending)->create($quiet);
    Policy::factory()->for($client)->create($quiet);
    Policy::factory()->for($client)->status(PolicyStatus::Cancelled)->create($quiet);

    $response = $this->actingAs($agent)->getJson(route('api.v1.policies.attention'))->assertOk();

    expect($response->json('data.*.policy.id'))->toBe([$lapsed->id, $stuck->id, $paymentDue->id, $renewing->id])
        ->and($response->json('data.0.reasons.0.code'))->toBe('lapsed')
        ->and($response->json('data.1.reasons.0.code'))->toBe('pending')
        ->and($response->json('data.2.reasons.0'))->toBe(['code' => 'payment_due', 'label' => 'Premium due soon', 'date' => '2026-10-20'])
        ->and($response->json('data.3.reasons.0'))->toBe(['code' => 'renewal_due', 'label' => 'Renewal coming up', 'date' => '2026-11-05']);
});

it('flags a renewal date that has passed', function () {
    $agent = User::factory()->create();
    Policy::factory()->for(Client::factory()->for($agent))->create(['payment_frequency' => PaymentFrequency::Single, 'renewal_date' => '2026-10-01']);

    $this->actingAs($agent)
        ->getJson(route('api.v1.policies.attention'))
        ->assertJsonPath('data.0.reasons.0.code', 'renewal_overdue');
});

it('summarizes commission received, receivable and expected', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
    // 2,000 a month at 25% is 6,000 a year in commission.
    $monthly = ['premium' => 2000, 'payment_frequency' => PaymentFrequency::Monthly, 'commission_rate' => 25];

    Policy::factory()->for($client)->create([...$monthly, 'commission_received_at' => '2026-03-10']);
    Policy::factory()->for($client)->create([...$monthly, 'commission_received_at' => '2025-12-20']);
    Policy::factory()->for($client)->create($monthly);
    Policy::factory()->for($client)->status(PolicyStatus::Pending)->create(['premium' => 40000, 'payment_frequency' => PaymentFrequency::Annual, 'commission_rate' => 15]);
    Policy::factory()->for($client)->status(PolicyStatus::Cancelled)->create($monthly);
    Policy::factory()->create($monthly);

    $this->actingAs($agent)
        ->getJson(route('api.v1.commissions.summary'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'received_this_year' => 6000,
            'receivable' => 6000,
            'expected' => 6000,
            'annual_premium_in_force' => 72000,
            'policies_in_force' => 3,
            'pending_applications' => 1,
        ]]);
});
