<?php

declare(strict_types=1);

use App\Actions\Leads\ScoreLead;
use App\Enums\LeadInterest;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function scored(Lead $lead): Lead
{
    return app(ScoreLead::class)->handle($lead->fresh());
}

it('scores a brand new prospect low, with its reasons', function () {
    $lead = Lead::factory()->create(['interest' => null, 'phone' => null]);

    $lead = scored($lead);

    expect($lead->score)->toBe(25)
        ->and($lead->score_reasons)->toBe([
            ['label' => 'Prospect', 'points' => 10],
            ['label' => 'In touch in the last 3 days', 'points' => 15],
        ]);
});

it('scores a qualified lead with a meeting coming up as hot', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->status(LeadStatus::Qualified)->create([
        'interest' => LeadInterest::Life,
        'meeting_at' => now()->addDays(2),
        'created_at' => now()->subMonth(),
    ]);
    $this->actingAs($agent)->postJson(route('api.v1.leads.notes.store', $lead), ['body' => 'Wants a quote for the family.'])->assertCreated();

    $lead->refresh();

    expect($lead->score)->toBe(95)
        ->and(array_column($lead->score_reasons, 'label'))->toBe([
            'Qualified', 'In touch in the last 3 days', 'Meeting coming up', 'Interested in Life', 'Has email and phone',
        ]);
});

it('marks a lead down when nobody has been in touch for weeks', function () {
    $lead = Lead::factory()->create(['interest' => null, 'phone' => null, 'created_at' => now()->subDays(40)]);

    $lead = scored($lead);

    expect($lead->score)->toBe(0)
        ->and($lead->score_reasons[1])->toBe(['label' => 'No contact in 40 days', 'points' => -20]);
});

it('counts an overdue follow-up against the lead', function () {
    $lead = Lead::factory()->create(['interest' => null, 'phone' => null]);
    FollowUp::factory()->for($lead)->dueAt(now()->subDay())->create();

    expect(scored($lead)->score_reasons)->toContain(['label' => 'Overdue follow-up', 'points' => -10]);
});

it('rewards sources that convert well for the agent', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->count(2)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for($agent)->status(LeadStatus::Converted)->create(['source' => LeadSource::Referral]);
    $lead = Lead::factory()->for($agent)->create(['source' => LeadSource::Referral]);

    expect(scored($lead)->score_reasons)->toContain(['label' => 'Referral leads convert well for you (25%)', 'points' => 5]);
});

it('leaves clients and lost leads unscored', function (LeadStatus $status) {
    expect(scored(Lead::factory()->status($status)->create(['score' => 50]))->score)->toBeNull();
})->with([LeadStatus::Converted, LeadStatus::Lost]);

it('rescores a lead when its status changes and sorts the list by score', function () {
    $agent = User::factory()->create();
    $cold = Lead::factory()->for($agent)->create();
    $warm = Lead::factory()->for($agent)->create();
    Lead::factory()->for($agent)->status(LeadStatus::Lost)->create();
    $this->actingAs($agent);

    $this->patchJson(route('api.v1.leads.status', $cold), ['status' => 'contacted'])->assertOk();
    $this->patchJson(route('api.v1.leads.status', $warm), ['status' => 'qualified'])
        ->assertJsonPath('data.score_band', 'hot');

    $this->getJson(route('api.v1.leads.index', ['sort' => 'score']))
        ->assertJsonPath('data.0.id', $warm->id)
        ->assertJsonPath('data.1.id', $cold->id)
        ->assertJsonPath('data.1.score_band', 'warm')
        ->assertJsonPath('data.2.score', null);
});

it('rescores every lead overnight', function () {
    $lead = Lead::factory()->create(['interest' => null, 'phone' => null]);
    scored($lead);

    Carbon::setTestNow(now()->addDays(20));
    $this->artisan('leads:rescore')->expectsOutputToContain('Scored 1 leads.')->assertSuccessful();

    expect($lead->fresh()->score)->toBe(0);
});
