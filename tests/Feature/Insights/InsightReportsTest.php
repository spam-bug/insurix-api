<?php

declare(strict_types=1);

use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Enums\RenewalOutcome;
use App\Enums\RenewalTerm;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Policy;
use App\Models\PolicyRenewal;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function reports(User $agent, string $query = ''): array
{
    return test()->actingAs($agent)->getJson(ws($agent).'/insights/reports'.$query)->assertOk()->json('data');
}

it('needs a signed-in agent', function () {
    $agent = User::factory()->create();
    $this->getJson(ws($agent).'/insights/reports')->assertUnauthorized();
    $this->getJson(ws($agent).'/insights/next-actions')->assertUnauthorized();
});

it('measures conversion by lead source, sources with enough leads first', function () {
    $agent = User::factory()->create();
    Lead::factory()->for(workspaceOf($agent))->count(2)->status(LeadStatus::Converted)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Lost)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for(workspaceOf($agent))->create(['source' => LeadSource::Referral]);
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Converted)->create(['source' => LeadSource::Manual]);
    Lead::factory()->for(workspaceOf($agent))->count(2)->create(['source' => LeadSource::Manual]);
    Lead::factory()->for(workspaceOf($agent))->count(2)->create(['source' => LeadSource::ShareableForm]);
    Lead::factory()->count(3)->status(LeadStatus::Converted)->create(['source' => LeadSource::SocialMedia]);

    expect(reports($agent)['lead_sources'])->toBe([
        ['source' => 'referral', 'label' => 'Referral', 'leads' => 4, 'clients' => 2, 'lost' => 1, 'rate_percent' => 50, 'enough_data' => true],
        ['source' => 'manual', 'label' => 'Manual entry', 'leads' => 3, 'clients' => 1, 'lost' => 0, 'rate_percent' => 33, 'enough_data' => true],
        ['source' => 'shareable_form', 'label' => 'Shareable form', 'leads' => 2, 'clients' => 0, 'lost' => 0, 'rate_percent' => 0, 'enough_data' => false],
    ]);
});

it('measures how long leads promoted in the period took to become clients', function () {
    $agent = User::factory()->create();
    $promoted = fn (string $added, string $promotedAt) => LeadActivity::factory()
        ->for(Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Converted)->create(['created_at' => $added]))
        ->create(['type' => LeadActivityType::Promoted, 'created_at' => $promotedAt]);

    $promoted('2026-09-01', '2026-09-11');
    $promoted('2026-08-01', '2026-08-31');
    $promoted('2026-07-01', '2026-07-03');
    $promoted('2025-01-01', '2025-02-01');
    LeadActivity::factory()->create(['type' => LeadActivityType::Promoted]);

    expect(reports($agent, '?period=year')['pipeline_speed'])->toBe(['average_days' => 14, 'median_days' => 10, 'converted' => 3])
        ->and(reports($agent)['pipeline_speed'])->toBe(['average_days' => null, 'median_days' => null, 'converted' => 0]);
});

it('has no pipeline speed before any lead is promoted', function () {
    expect(reports(User::factory()->create())['pipeline_speed'])->toBe(['average_days' => null, 'median_days' => null, 'converted' => 0]);
});

it('lists open leads nobody has been in touch with for two weeks', function () {
    $agent = User::factory()->create();
    $quiet = Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Contacted)->create(['created_at' => now()->subDays(20), 'score' => 15]);
    $touched = Lead::factory()->for(workspaceOf($agent))->create(['created_at' => now()->subDays(30)]);
    LeadActivity::factory()->for($touched)->create(['created_at' => now()->subDays(3)]);
    $onlyEdited = Lead::factory()->for(workspaceOf($agent))->create(['created_at' => now()->subDays(40), 'score' => 5]);
    LeadActivity::factory()->for($onlyEdited)->create(['type' => LeadActivityType::Updated, 'created_at' => now()->subDay()]);
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Lost)->create(['created_at' => now()->subDays(40)]);
    Lead::factory()->for(workspaceOf($agent))->create(['created_at' => now()->subDays(5)]);

    expect(reports($agent)['stalled_leads'])->toBe([
        'quiet_days' => 14,
        'total' => 2,
        'leads' => [
            ['id' => $quiet->id, 'name' => $quiet->name, 'status' => ['value' => 'contacted', 'label' => 'Contacted'], 'score' => 15, 'days_quiet' => 20],
            ['id' => $onlyEdited->id, 'name' => $onlyEdited->name, 'status' => ['value' => 'prospect', 'label' => 'Prospect'], 'score' => 5, 'days_quiet' => 40],
        ],
    ]);
});

it('measures retention and counts renewals coming up', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    Policy::factory()->for($client)->count(2)->create();
    Policy::factory()->for($client)->create(['renewal_date' => '2026-11-20']);
    Policy::factory()->for($client)->status(PolicyStatus::Lapsed)->create();
    Policy::factory()->for($client)->status(PolicyStatus::Pending)->create(['renewal_date' => '2026-11-01']);
    Policy::factory()->create();

    expect(reports($agent)['retention'])->toBe([
        'in_force' => 3,
        'lapsed' => 1,
        'cancelled' => 0,
        'retention_percent' => 75,
        'renewals_coming_up' => 1,
        'renewals_recorded' => 0,
        'renewal_rate_percent' => null,
    ]);
});

it('works out renewals from set terms, with a shorter window for short terms', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $term = fn (RenewalTerm $term, string $effective) => ['renewal_term' => $term, 'effective_date' => $effective, 'renewal_date' => null];

    $annual = Policy::factory()->for($client)->create($term(RenewalTerm::Annual, '2025-12-01'));
    Policy::factory()->for($client)->create($term(RenewalTerm::Annual, '2026-03-01'));
    Policy::factory()->for($client)->create($term(RenewalTerm::Monthly, '2026-01-20'));
    Policy::factory()->for($client)->create($term(RenewalTerm::Monthly, '2026-01-01'));
    Policy::factory()->for($client)->create($term(RenewalTerm::Quarterly, '2026-08-01'));

    expect(reports($agent)['retention']['renewals_coming_up'])->toBe(2);

    $suggestion = collect(test()->getJson(ws($agent).'/insights/next-actions')->json('data'))->firstWhere('code', 'renewals_without_follow_up');
    expect($suggestion['title'])->toBe('2 renewals in the next 60 days have no follow-up yet')
        ->and($suggestion['detail'])->toBe("{$client->name} (Oct 20) and {$client->name} (Dec 1). Reach out early to keep them.");
});

it('ranks products by the commission their policies earn', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    // 2,000 a month at 25% is 6,000 a year in commission.
    $monthly = ['premium' => 2000, 'payment_frequency' => PaymentFrequency::Monthly, 'commission_rate' => 25];
    Policy::factory()->for($client)->count(2)->create([...$monthly, 'product_name' => 'Life Plus']);
    Policy::factory()->for($client)->create(['product_name' => 'Health Max', 'premium' => 30000, 'payment_frequency' => PaymentFrequency::Annual, 'commission_rate' => 15]);
    Policy::factory()->for($client)->status(PolicyStatus::Pending)->create([...$monthly, 'product_name' => 'Health Max', 'premium' => 90000]);

    expect(reports($agent)['top_products'])->toEqual([
        ['product_id' => null, 'name' => 'Life Plus', 'policies' => 2, 'annual_premium' => 48000, 'commission' => 12000],
        ['product_id' => null, 'name' => 'Health Max', 'policies' => 1, 'annual_premium' => 30000, 'commission' => 4500],
    ]);
});

it('charts commission received over the twelve months up to the period', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $monthly = ['premium' => 2000, 'payment_frequency' => PaymentFrequency::Monthly, 'commission_rate' => 25];
    Policy::factory()->for($client)->count(2)->create([...$monthly, 'commission_received_at' => '2026-10-05']);
    Policy::factory()->for($client)->create([...$monthly, 'commission_received_at' => '2025-12-20']);
    Policy::factory()->for($client)->create([...$monthly, 'commission_received_at' => '2025-10-31']);

    $months = reports($agent)['commission_by_month'];

    expect($months)->toHaveCount(12)
        ->and($months[0])->toEqual(['month' => '2025-11', 'received' => 0, 'policies' => 0])
        ->and($months[1])->toEqual(['month' => '2025-12', 'received' => 6000, 'policies' => 1])
        ->and($months[11])->toEqual(['month' => '2026-10', 'received' => 12000, 'policies' => 2]);

    $lastYear = reports($agent, '?period=year&offset=1')['commission_by_month'];

    expect($lastYear[0]['month'])->toBe('2025-01')
        ->and($lastYear[9])->toEqual(['month' => '2025-10', 'received' => 6000, 'policies' => 1])
        ->and($lastYear[11])->toEqual(['month' => '2025-12', 'received' => 6000, 'policies' => 1]);
});

it('describes the chosen period and steps back to earlier ones', function () {
    $agent = User::factory()->create();

    expect(reports($agent)['period'])->toBe([
        'value' => 'month', 'offset' => 0, 'label' => 'October 2026', 'previous_label' => 'September 2026',
        'starts_on' => '2026-10-01', 'ends_on' => '2026-10-31',
    ])
        ->and(reports($agent, '?period=quarter&offset=1')['period'])->toBe([
            'value' => 'quarter', 'offset' => 1, 'label' => 'Q3 2026', 'previous_label' => 'Q2 2026',
            'starts_on' => '2026-07-01', 'ends_on' => '2026-09-30',
        ])
        ->and(reports($agent, '?period=year&offset=2')['period']['label'])->toBe('2024');
});

it('rejects an unknown period or a negative offset', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)->getJson(ws($agent).'/insights/reports?period=week&offset=-1')
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['period', 'offset']);
});

it('measures the period figures next to the period before', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for(workspaceOf($agent))->create();
    $policy = Policy::factory()->for($client)->create(['premium' => 2000, 'payment_frequency' => PaymentFrequency::Monthly, 'commission_rate' => 25, 'commission_received_at' => '2026-10-02']);
    Policy::factory()->for($client)->create(['premium' => 2000, 'payment_frequency' => PaymentFrequency::Monthly, 'commission_rate' => 25, 'commission_received_at' => '2026-09-30']);
    $lead = fn (string $addedAt) => Lead::factory()->for(workspaceOf($agent))->create(['created_at' => $addedAt]);
    $closed = fn (string $at, ?LeadStatus $lostTo = null) => LeadActivity::factory()->for($lead('2026-08-01'))->create($lostTo
        ? ['type' => LeadActivityType::StatusChanged, 'properties' => ['from' => 'contacted', 'to' => $lostTo->value], 'created_at' => $at]
        : ['type' => LeadActivityType::Promoted, 'created_at' => $at]);
    $outcome = fn (string $dueOn, RenewalOutcome $outcome) => PolicyRenewal::forceCreate([
        'policy_id' => $policy->id, 'workspace_id' => workspaceOf($agent)->id, 'due_on' => $dueOn, 'outcome' => $outcome,
    ]);

    $lead('2026-10-05');
    $lead('2026-10-10');
    $lead('2026-09-15');
    $closed('2026-10-03');
    $closed('2026-10-04');
    $closed('2026-10-06', LeadStatus::Lost);
    $closed('2026-10-07', LeadStatus::Qualified);
    $closed('2026-09-20', LeadStatus::Lost);
    $outcome('2026-10-01', RenewalOutcome::Renewed);
    $outcome('2026-10-09', RenewalOutcome::Lapsed);
    Lead::factory()->create(['created_at' => '2026-10-05']);

    expect(reports($agent)['figures'])->toEqual([
        'current' => ['new_leads' => 2, 'won' => 2, 'lost' => 1, 'win_rate_percent' => 67, 'commission_received' => 6000, 'renewals_recorded' => 2, 'renewal_rate_percent' => 50],
        'previous' => ['new_leads' => 1, 'won' => 0, 'lost' => 1, 'win_rate_percent' => 0, 'commission_received' => 6000, 'renewals_recorded' => 0, 'renewal_rate_percent' => null],
    ]);
});

it('builds a funnel of how far the leads added in the period got, lost leads included', function () {
    $agent = User::factory()->create();
    $lead = fn (LeadStatus $status, array $history = []) => tap(
        Lead::factory()->for(workspaceOf($agent))->status($status)->create(['created_at' => '2026-10-05']),
        fn (Lead $lead) => collect($history)->each(fn (array $change) => LeadActivity::factory()->for($lead)->create(['type' => LeadActivityType::StatusChanged, 'properties' => $change])),
    );

    $lead(LeadStatus::Prospect);
    $lead(LeadStatus::Contacted);
    $lead(LeadStatus::Qualified);
    $lead(LeadStatus::Converted);
    $lead(LeadStatus::Lost, [['from' => 'prospect', 'to' => 'meeting_scheduled'], ['from' => 'meeting_scheduled', 'to' => 'lost']]);
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Converted)->create(['created_at' => '2026-09-05']);

    expect(reports($agent)['funnel'])->toBe([
        'stages' => [
            ['status' => 'prospect', 'label' => 'Prospect', 'reached' => 5, 'conversion_percent' => null],
            ['status' => 'contacted', 'label' => 'Contacted', 'reached' => 4, 'conversion_percent' => 80],
            ['status' => 'meeting_scheduled', 'label' => 'Meeting scheduled', 'reached' => 3, 'conversion_percent' => 75],
            ['status' => 'qualified', 'label' => 'Qualified', 'reached' => 2, 'conversion_percent' => 67],
            ['status' => 'converted', 'label' => 'Client', 'reached' => 1, 'conversion_percent' => 50],
        ],
        'lost' => 1,
    ]);
});
