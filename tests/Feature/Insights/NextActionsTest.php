<?php

declare(strict_types=1);

use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\Policy;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function nextActions(User $agent): array
{
    return test()->actingAs($agent)->getJson('/api/v1/insights/next-actions')->assertOk()->json('data');
}

it('suggests adding leads to a brand new agent', function () {
    expect(nextActions(User::factory()->create()))->toBe([
        ['code' => 'no_new_leads', 'title' => 'Add your first leads', 'detail' => "Share your form link, or add someone you've talked to.", 'count' => 0, 'lead_id' => null, 'source' => null],
    ]);
});

it('suggests nothing when the agent is on top of things', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->create();

    expect(nextActions($agent))->toBe([]);
});

it('puts the most pressing suggestions first and keeps to five', function () {
    $agent = User::factory()->create();

    FollowUp::factory()->for(Lead::factory()->for($agent)->create(['source' => LeadSource::Manual]))->dueAt(now()->subDay())->create();
    $hot = Lead::factory()->for($agent)->status(LeadStatus::Qualified)->create(['source' => LeadSource::Manual, 'score' => 80, 'created_at' => now()->subDays(9)]);
    Policy::factory()->for(Client::factory()->for($agent)->create(['name' => 'Ana Reyes']))->create(['renewal_date' => '2026-12-01']);
    Policy::factory()->for(Client::factory()->for($agent))->status(PolicyStatus::Pending)->create(['created_at' => now()->subDays(20)]);
    Lead::factory()->for($agent)->count(2)->status(LeadStatus::Converted)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for($agent)->create(['source' => LeadSource::Referral]);
    Lead::factory()->for($agent)->create(['source' => LeadSource::Manual, 'score' => 30, 'created_at' => now()->subDays(20)]);

    $suggestions = nextActions($agent);

    expect(array_column($suggestions, 'code'))->toBe([
        'overdue_follow_ups', 'hot_leads_waiting', 'renewals_without_follow_up', 'stuck_applications', 'ask_for_referrals',
    ])
        ->and($suggestions[0]['title'])->toBe('1 follow-up is overdue')
        ->and($suggestions[1])->toMatchArray([
            'title' => "{$hot->name} is hot but hasn't heard from you in 9 days",
            'lead_id' => $hot->id,
        ])
        ->and($suggestions[2]['title'])->toBe('1 renewal in the next 60 days has no follow-up yet')
        ->and($suggestions[2]['detail'])->toBe('Ana Reyes (Dec 1). Reach out early to keep them.')
        ->and($suggestions[4])->toMatchArray(['title' => 'Referrals convert at 67% for you', 'source' => 'referral']);
});

it('leaves out renewals that already have a follow-up', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for($agent)->create();
    $policy = Policy::factory()->for(Client::factory()->for($agent)->create(['lead_id' => $lead->id]))->create(['renewal_date' => '2026-12-01']);
    FollowUp::factory()->for($lead)->create(['policy_id' => $policy->id]);

    expect(array_column(nextActions($agent), 'code'))->not->toContain('renewals_without_follow_up');
});

it('points to the one lead that has gone quiet', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->create();
    $quiet = Lead::factory()->for($agent)->create(['score' => 20, 'created_at' => now()->subDays(16)]);

    expect(nextActions($agent))->toBe([
        ['code' => 'stalled_leads', 'title' => '1 lead has gone quiet for 2+ weeks', 'detail' => 'Follow up, or mark them lost to keep your pipeline honest.', 'count' => 1, 'lead_id' => $quiet->id, 'source' => null],
    ]);
});

it('notices when no new leads have come in for two weeks', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->status(LeadStatus::Lost)->create(['created_at' => now()->subDays(21)]);

    expect(nextActions($agent)[0])->toMatchArray(['code' => 'no_new_leads', 'title' => 'No new leads in 21 days', 'count' => 21]);
});
