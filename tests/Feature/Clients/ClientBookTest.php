<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

it('lists each client’s policies, premium and next renewal in force, and last contact', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    Policy::factory()->for($client)->create(['payment_frequency' => PaymentFrequency::Annual, 'premium' => 12000, 'renewal_date' => '2027-03-01']);
    Policy::factory()->for($client)->create(['payment_frequency' => PaymentFrequency::Annual, 'premium' => 6000, 'renewal_date' => '2026-12-05']);
    Policy::factory()->for($client)->status(PolicyStatus::Lapsed)->create(['payment_frequency' => PaymentFrequency::Annual, 'premium' => 99000]);
    FollowUp::factory()->forClient($client)->create(['completed_at' => '2026-10-10 03:00:00']);

    $this->actingAs($agent)->getJson(ws($agent).'/clients')
        ->assertJsonPath('data.0.in_force_policies', 2)
        ->assertJsonPath('data.0.annual_premium', 18000)
        ->assertJsonPath('data.0.next_renewal_on', '2026-12-05')
        ->assertJsonPath('data.0.last_contact_at', '2026-10-10T03:00:00+00:00');
});

it('sorts clients by premium or by who was contacted longest ago', function () {
    $agent = User::factory()->create();
    $small = Client::factory()->for(workspaceOf($agent))->create(['name' => 'Small']);
    $large = Client::factory()->for(workspaceOf($agent))->create(['name' => 'Large']);
    $never = Client::factory()->for(workspaceOf($agent))->create(['name' => 'Never']);
    Policy::factory()->for($small)->create(['premium' => 100]);
    Policy::factory()->for($large)->create(['premium' => 9000]);
    FollowUp::factory()->forClient($small)->create(['completed_at' => now()->subDays(30)]);
    FollowUp::factory()->forClient($large)->create(['completed_at' => now()->subDay()]);

    $this->actingAs($agent);
    expect($this->getJson(ws($agent).'/clients?sort=premium')->json('data.*.name'))->toBe(['Large', 'Small', 'Never'])
        ->and($this->getJson(ws($agent).'/clients?sort=last_contact')->json('data.*.name'))->toBe(['Never', 'Small', 'Large'])
        ->and($this->getJson(ws($agent).'/clients?sort=name')->json('data.*.name'))->toBe(['Large', 'Never', 'Small']);
});

it('summarizes the book', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create(['created_at' => '2026-09-20']);
    Client::factory()->for(workspaceOf($agent))->create();
    Policy::factory()->for($client)->count(2)->create(['payment_frequency' => PaymentFrequency::Annual, 'premium' => 5000]);
    Client::factory()->create();

    $this->actingAs($agent)->getJson(ws($agent).'/clients/summary')
        ->assertExactJson(['data' => ['clients' => 2, 'with_policies' => 1, 'annual_premium' => 10000, 'added_this_month' => 1]]);
});

it('lists birthdays in the next 30 days, soonest first, wrapping into next year', function () {
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);
    Client::factory()->for($workspace)->create(['name' => 'Later', 'birthdate' => '1990-11-10']);
    Client::factory()->for($workspace)->create(['name' => 'Today', 'birthdate' => '1980-10-17']);
    Client::factory()->for($workspace)->create(['name' => 'Passed', 'birthdate' => '1985-10-16']);
    Client::factory()->for($workspace)->create(['name' => 'Far', 'birthdate' => '1985-12-25']);

    $this->actingAs($agent)->getJson(ws($agent).'/clients/birthdays')
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.client.name', 'Today')
        ->assertJsonPath('data.0.turns', 46)
        ->assertJsonPath('data.1.client.name', 'Later')
        ->assertJsonPath('data.1.birthday', '2026-11-10');
});
