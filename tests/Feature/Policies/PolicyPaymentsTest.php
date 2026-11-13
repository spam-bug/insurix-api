<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Policy;
use App\Models\PolicyPayment;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

/** A monthly 2,500 policy at 30% in year one and 5% after, effective on the given day. */
function trackedPolicy(User $agent, string $effectiveDate, array $attributes = []): Policy
{
    return Policy::factory()->for(Client::factory()->for(workspaceOf($agent)))->create([
        'effective_date' => $effectiveDate,
        'renewal_commission_rate' => 5,
        'created_at' => $effectiveDate,
        ...$attributes,
    ]);
}

it('records the next unpaid premium with its estimated commission and moves on to the one after', function () {
    $agent = User::factory()->create();
    $policy = trackedPolicy($agent, '2026-10-17');

    $this->actingAs($agent)->postJson(ws($agent)."/policies/{$policy->id}/payments", ['paid_on' => '2026-10-17'])
        ->assertCreated()
        ->assertJsonPath('data.next_payment_due', '2026-11-17')
        ->assertJsonPath('data.payments.0.due_on', '2026-10-17')
        ->assertJsonPath('data.payments.0.amount', 2500)
        ->assertJsonPath('data.payments.0.commission_expected', 750)
        ->assertJsonPath('data.payments.0.commission_received', null)
        ->assertJsonPath('data.events.0.type', 'premium_paid');
});

it('estimates commission at the renewal rate after the first policy year', function () {
    $agent = User::factory()->create();
    $policy = trackedPolicy($agent, '2025-09-17', ['created_at' => '2026-10-01']);

    $this->actingAs($agent)->postJson(ws($agent)."/policies/{$policy->id}/payments", ['paid_on' => '2026-10-17', 'amount' => 3000])
        ->assertCreated()
        ->assertJsonPath('data.payments.0.due_on', '2026-10-17')
        ->assertJsonPath('data.payments.0.commission_expected', 150);
});

it('flags an unpaid premium through its grace period, then as a lapse risk', function () {
    $agent = User::factory()->create();
    trackedPolicy($agent, '2026-09-17', ['renewal_term' => null, 'renewal_date' => null]);
    $this->actingAs($agent);

    $this->getJson(ws($agent).'/policies/attention')
        ->assertJsonPath('data.0.reasons.0.code', 'payment_overdue')
        ->assertJsonPath('data.0.reasons.0.label', 'Premium unpaid: 1 day left in the grace period')
        ->assertJsonPath('data.0.policy.next_payment_due', '2026-09-17');

    Carbon::setTestNow('2026-10-20 02:00:00');
    Cache::flush();

    $this->getJson(ws($agent).'/policies/attention')->assertJsonPath('data.0.reasons.0.code', 'payment_lapse_risk');
});

it('records the commission the agent actually received and counts it in the summary', function () {
    $agent = User::factory()->create();
    $payment = PolicyPayment::factory()->for(trackedPolicy($agent, '2026-10-17'))->create(['due_on' => '2026-10-17']);
    $this->actingAs($agent);

    $this->getJson(ws($agent).'/commissions/summary')->assertJsonPath('data.receivable', 750)->assertJsonPath('data.received_this_year', 0);

    $this->putJson(ws($agent)."/payments/{$payment->id}", ['paid_on' => '2026-10-17', 'amount' => 2500, 'commission_received' => 712.5])
        ->assertOk()
        ->assertJsonPath('data.payments.0.commission_received', 712.5)
        ->assertJsonPath('data.payments.0.commission_received_on', '2026-10-17')
        ->assertJsonPath('data.commission_received_total', 712.5);

    $this->getJson(ws($agent).'/commissions/summary')->assertJsonPath('data.receivable', 0)->assertJsonPath('data.received_this_year', 712.5);
});

it('removes only the latest payment, and never another workspace’s', function () {
    $agent = User::factory()->create();
    $policy = trackedPolicy($agent, '2026-09-17');
    $first = PolicyPayment::factory()->for($policy)->create(['due_on' => '2026-09-17']);
    $latest = PolicyPayment::factory()->for($policy)->create(['due_on' => '2026-10-17']);

    $this->actingAs(User::factory()->create())->deleteJson(ws($agent)."/payments/{$latest->id}")->assertNotFound();

    $this->actingAs($agent);
    $this->deleteJson(ws($agent)."/payments/{$first->id}")->assertUnprocessable()->assertJsonValidationErrors('payment');
    $this->deleteJson(ws($agent)."/payments/{$latest->id}")->assertNoContent();

    expect($policy->payments()->pluck('id')->all())->toBe([$first->id]);
});

it('counts a renewed annual policy’s premium as paid', function () {
    $agent = User::factory()->create();
    $policy = trackedPolicy($agent, '2025-10-10', [
        'premium' => 24000, 'payment_frequency' => PaymentFrequency::Annual, 'commission_rate' => 20, 'renewal_commission_rate' => 20,
        'renewal_term' => RenewalTerm::Annual, 'renewal_date' => null, 'created_at' => '2026-01-01',
    ]);

    $this->actingAs($agent)->postJson(ws($agent)."/policies/{$policy->id}/renewals", ['due_on' => '2026-10-10', 'outcome' => 'renewed'])
        ->assertCreated()
        ->assertJsonPath('data.payments.0.due_on', '2026-10-10')
        ->assertJsonPath('data.payments.0.commission_expected', 4800)
        ->assertJsonPath('data.next_payment_due', '2027-10-10');
});
