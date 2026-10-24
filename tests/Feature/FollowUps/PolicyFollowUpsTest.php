<?php

declare(strict_types=1);

use App\Enums\FollowUpType;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

/** 10:00 on 17 October in Manila. */
beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function clientWithLead(User $agent): Client
{
    return Client::factory()->for(workspaceOf($agent))->create(['lead_id' => Lead::factory()->for(workspaceOf($agent))->create()->id]);
}

it('reminds the agent about a renewal two weeks ahead, once', function () {
    $agent = User::factory()->create();
    $client = clientWithLead($agent);
    $policy = Policy::factory()->for($client)->create([
        'product_name' => 'Sun FitandWell',
        'payment_frequency' => PaymentFrequency::Annual,
        'effective_date' => '2026-03-01',
        'renewal_date' => '2026-11-10',
    ]);

    $this->artisan('policies:queue-follow-ups')->expectsOutputToContain('Created 1 policy follow-ups.')->assertSuccessful();
    $this->artisan('policies:queue-follow-ups')->expectsOutputToContain('Created 0 policy follow-ups.');

    $followUp = $client->lead->followUps()->sole();
    expect($followUp->policy_id)->toBe($policy->id)
        ->and($followUp->type)->toBe(FollowUpType::Call)
        ->and($followUp->title)->toBe('Renewal: Sun FitandWell renews Nov 10')
        ->and($followUp->due_at->toDateTimeString())->toBe('2026-10-27 01:00:00');
});

it('reminds the agent about a premium due this week, right away when its hour has passed', function () {
    $agent = User::factory()->create();
    $client = clientWithLead($agent);
    Policy::factory()->for($client)->create([
        'product_name' => 'PRULink',
        'payment_frequency' => PaymentFrequency::Monthly,
        'effective_date' => '2026-01-19',
        'renewal_date' => '2027-01-19',
    ]);

    $this->artisan('policies:queue-follow-ups')->assertSuccessful();

    $followUp = $client->lead->followUps()->sole();
    expect($followUp->type)->toBe(FollowUpType::Message)
        ->and($followUp->title)->toBe('Premium due: PRULink on Oct 19')
        ->and($followUp->due_at->toDateTimeString())->toBe('2026-10-17 02:00:00');
});

it('skips policies that are not in force', function () {
    $agent = User::factory()->create();
    $dueSoon = ['payment_frequency' => PaymentFrequency::Annual, 'effective_date' => '2026-03-01', 'renewal_date' => '2026-11-01'];
    Policy::factory()->for(clientWithLead($agent))->status(PolicyStatus::Lapsed)->create($dueSoon);

    $this->artisan('policies:queue-follow-ups')->expectsOutputToContain('Created 0 policy follow-ups.');
});

it('reminds the agent about a client added without a lead, on the client', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    Policy::factory()->for($client)->create(['payment_frequency' => PaymentFrequency::Annual, 'effective_date' => '2026-03-01', 'renewal_date' => '2026-11-01']);

    $this->artisan('policies:queue-follow-ups')->assertSuccessful();

    expect($client->followUps()->sole())->lead_id->toBeNull()->policy_id->not->toBeNull();
});
