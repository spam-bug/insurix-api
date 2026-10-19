<?php

use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function reports(User $agent): array
{
    return test()->actingAs($agent)->getJson(route('api.v1.insights.reports'))->assertOk()->json('data');
}

it('needs a signed-in agent', function () {
    $this->getJson(route('api.v1.insights.reports'))->assertUnauthorized();
    $this->getJson(route('api.v1.insights.next-actions'))->assertUnauthorized();
});

it('measures conversion by lead source, sources with enough leads first', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->count(2)->status(LeadStatus::Converted)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for($agent)->status(LeadStatus::Lost)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for($agent)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for($agent)->status(LeadStatus::Converted)->create(['source' => LeadSource::Manual]);
    Lead::factory()->for($agent)->count(2)->create(['source' => LeadSource::Manual]);
    Lead::factory()->for($agent)->count(2)->create(['source' => LeadSource::ShareableForm]);
    Lead::factory()->count(3)->status(LeadStatus::Converted)->create(['source' => LeadSource::SocialMedia]);

    expect(reports($agent)['lead_sources'])->toBe([
        ['source' => 'referral', 'label' => 'Referral', 'leads' => 4, 'clients' => 2, 'lost' => 1, 'rate_percent' => 50, 'enough_data' => true],
        ['source' => 'manual', 'label' => 'Manual entry', 'leads' => 3, 'clients' => 1, 'lost' => 0, 'rate_percent' => 33, 'enough_data' => true],
        ['source' => 'shareable_form', 'label' => 'Shareable form', 'leads' => 2, 'clients' => 0, 'lost' => 0, 'rate_percent' => 0, 'enough_data' => false],
    ]);
});

it('measures how long leads promoted in the last year took to become clients', function () {
    $agent = User::factory()->create();
    $promoted = fn (string $added, string $promotedAt) => LeadActivity::factory()
        ->for(Lead::factory()->for($agent)->status(LeadStatus::Converted)->create(['created_at' => $added]))
        ->create(['type' => LeadActivityType::Promoted, 'created_at' => $promotedAt]);

    $promoted('2026-09-01', '2026-09-11');
    $promoted('2026-08-01', '2026-08-31');
    $promoted('2026-07-01', '2026-07-03');
    $promoted('2025-01-01', '2025-02-01');
    LeadActivity::factory()->create(['type' => LeadActivityType::Promoted]);

    expect(reports($agent)['pipeline_speed'])->toBe(['average_days' => 14, 'median_days' => 10, 'converted' => 3]);
});

it('has no pipeline speed before any lead is promoted', function () {
    expect(reports(User::factory()->create())['pipeline_speed'])->toBe(['average_days' => null, 'median_days' => null, 'converted' => 0]);
});

it('lists open leads nobody has been in touch with for two weeks', function () {
    $agent = User::factory()->create();
    $quiet = Lead::factory()->for($agent)->status(LeadStatus::Contacted)->create(['created_at' => now()->subDays(20), 'score' => 15]);
    $touched = Lead::factory()->for($agent)->create(['created_at' => now()->subDays(30)]);
    LeadActivity::factory()->for($touched)->create(['created_at' => now()->subDays(3)]);
    $onlyEdited = Lead::factory()->for($agent)->create(['created_at' => now()->subDays(40), 'score' => 5]);
    LeadActivity::factory()->for($onlyEdited)->create(['type' => LeadActivityType::Updated, 'created_at' => now()->subDay()]);
    Lead::factory()->for($agent)->status(LeadStatus::Lost)->create(['created_at' => now()->subDays(40)]);
    Lead::factory()->for($agent)->create(['created_at' => now()->subDays(5)]);

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
    $client = Client::factory()->for($agent)->create();
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
    ]);
});

it('ranks products by the commission their policies earn', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
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

it('charts commission received over the last twelve months', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
    $monthly = ['premium' => 2000, 'payment_frequency' => PaymentFrequency::Monthly, 'commission_rate' => 25];
    Policy::factory()->for($client)->count(2)->create([...$monthly, 'commission_received_at' => '2026-10-05']);
    Policy::factory()->for($client)->create([...$monthly, 'commission_received_at' => '2025-12-20']);
    Policy::factory()->for($client)->create([...$monthly, 'commission_received_at' => '2025-10-31']);

    $months = reports($agent)['commission_by_month'];

    expect($months)->toHaveCount(12)
        ->and($months[0])->toEqual(['month' => '2025-11', 'received' => 0, 'policies' => 0])
        ->and($months[1])->toEqual(['month' => '2025-12', 'received' => 6000, 'policies' => 1])
        ->and($months[11])->toEqual(['month' => '2026-10', 'received' => 12000, 'policies' => 2]);
});
