<?php

declare(strict_types=1);

use App\Actions\FollowUps\QueuePolicyFollowUps;
use App\Enums\PolicyStatus;
use App\Enums\RenewalOutcome;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\PolicyRenewal;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function annualPolicy(string $effective, array $attributes = []): Policy
{
    $client = Client::factory()->create(['name' => 'Ana Reyes', 'lead_id' => null]);

    return Policy::factory()->for($client)->create([
        'renewal_term' => RenewalTerm::Annual,
        'effective_date' => $effective,
        'renewal_date' => null,
        ...$attributes,
    ]);
}

it('asks for the outcome of a renewal date that has passed', function () {
    $policy = annualPolicy('2025-10-10');
    $this->actingAs($agent = memberOf($policy->workspace));

    $this->getJson(ws($agent)."/policies/{$policy->id}")
        ->assertJsonPath('data.tracks_renewal_outcomes', true)
        ->assertJsonPath('data.renewal_awaiting_outcome', '2026-10-10');
    $this->getJson(ws($agent).'/policies/attention')
        ->assertJsonPath('data.0.reasons.0', ['code' => 'renewal_overdue', 'label' => 'Renewal date has passed: record how it went', 'date' => '2026-10-10']);
    expect(collect($this->getJson(ws($agent).'/insights/next-actions')->json('data'))->firstWhere('code', 'renewals_awaiting_outcome'))
        ->toMatchArray(['title' => '1 renewal is waiting for its outcome', 'detail' => 'Ana Reyes (Oct 10). Record whether they renewed or lapsed.']);
});

it('records a renewal and moves on to the next one', function () {
    $policy = annualPolicy('2025-10-10');
    $this->actingAs($agent = memberOf($policy->workspace));

    $this->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-10', 'outcome' => 'renewed', 'notes' => 'Paid on time'])
        ->assertCreated()
        ->assertJsonPath('data.status.value', 'in_force')
        ->assertJsonPath('data.renewal_awaiting_outcome', null)
        ->assertJsonPath('data.renewal_date', '2027-10-10')
        ->assertJsonPath('data.renewals.0', fn (array $renewal) => $renewal['due_on'] === '2026-10-10' && $renewal['outcome']['value'] === 'renewed' && $renewal['notes'] === 'Paid on time')
        ->assertJsonPath('data.events.0.type', 'renewal_recorded');

    $this->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-10', 'outcome' => 'renewed'])
        ->assertJsonValidationErrors(['due_on' => 'waiting for an outcome']);
    $this->getJson(ws($agent).'/policies/attention')->assertJsonCount(0, 'data');
});

it('sets the policy lapsed or cancelled when it does not renew', function (string $outcome, string $status) {
    $policy = annualPolicy('2025-10-10');

    $this->actingAs($agent = memberOf($policy->workspace))
        ->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-10', 'outcome' => $outcome])
        ->assertCreated()
        ->assertJsonPath('data.status.value', $status);
})->with([['lapsed', 'lapsed'], ['not_renewing', 'cancelled']]);

it('needs the next date when a custom-date policy renews', function () {
    $policy = annualPolicy('2025-01-01', ['renewal_term' => RenewalTerm::Custom, 'renewal_date' => '2026-10-01']);
    $this->actingAs($agent = memberOf($policy->workspace));

    $this->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-01', 'outcome' => 'renewed'])
        ->assertJsonValidationErrors(['next_renewal_date' => 'renews next']);

    $this->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-01', 'outcome' => 'renewed', 'next_renewal_date' => '2027-04-01'])
        ->assertCreated()
        ->assertJsonPath('data.renewal_date', '2027-04-01');
});

it('records a renewal early, so its reminder is not queued', function () {
    $lead = Lead::factory()->create();
    $client = Client::factory()->for($lead->workspace)->create(['lead_id' => $lead->id]);
    $policy = Policy::factory()->for($client)->create([
        'renewal_term' => RenewalTerm::Annual,
        'effective_date' => '2025-11-05',
        'renewal_date' => null,
        'payment_frequency' => 'annual',
    ]);

    $this->actingAs($agent = memberOf($client->workspace))
        ->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-11-05', 'outcome' => 'renewed'])
        ->assertCreated();

    expect(app(QueuePolicyFollowUps::class)->handle($client->workspace))->toBe(0);
});

it('leaves monthly and quarterly terms out of renewal outcomes', function () {
    $policy = annualPolicy('2026-01-20', ['renewal_term' => RenewalTerm::Monthly]);

    $this->actingAs($agent = memberOf($policy->workspace))
        ->getJson(ws($agent)."/policies/{$policy->id}")
        ->assertJsonPath('data.tracks_renewal_outcomes', false)
        ->assertJsonPath('data.renewal_awaiting_outcome', null);
    $this->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-20', 'outcome' => 'renewed'])
        ->assertJsonValidationErrors('due_on');
});

it('keeps other agents from recording renewals', function () {
    $policy = annualPolicy('2025-10-10');

    $this->actingAs($agent = User::factory()->create())
        ->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-10', 'outcome' => 'renewed'])
        ->assertNotFound();
});

it('measures the renewal rate over the last twelve months', function () {
    $agent = User::factory()->create();
    $policy = Policy::factory()->for(Client::factory()->for(workspaceOf($agent)))->create();
    $outcome = fn (string $dueOn, RenewalOutcome $outcome) => PolicyRenewal::forceCreate([
        'policy_id' => $policy->id, 'workspace_id' => workspaceOf($agent)->id, 'due_on' => $dueOn, 'outcome' => $outcome,
    ]);

    $outcome('2026-09-01', RenewalOutcome::Renewed);
    $outcome('2026-06-01', RenewalOutcome::Renewed);
    $outcome('2026-03-01', RenewalOutcome::Renewed);
    $outcome('2026-01-01', RenewalOutcome::Lapsed);
    $outcome('2025-06-01', RenewalOutcome::NotRenewing);

    $this->actingAs($agent)
        ->getJson(ws($agent).'/insights/reports')
        ->assertJsonPath('data.retention.renewals_recorded', 4)
        ->assertJsonPath('data.retention.renewal_rate_percent', 75);
});

it('does not ask about renewals of policies that are no longer in force', function () {
    $policy = annualPolicy('2025-10-10', ['status' => PolicyStatus::Lapsed]);

    $this->actingAs($agent = memberOf($policy->workspace))
        ->getJson(ws($agent)."/policies/{$policy->id}")
        ->assertJsonPath('data.renewal_awaiting_outcome', null);
});
