<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * 10:00 on 17 October in Manila, where the agent works. Their day ends at 15:59:59 UTC.
 */
beforeEach(fn () => Carbon::setTestNow('2026-10-17 02:00:00'));

function followUpsFor(User $agent): array
{
    $lead = Lead::factory()->for(workspaceOf($agent))->create();

    return [
        'overdue' => FollowUp::factory()->for($lead)->dueAt(Carbon::parse('2026-10-16 23:00:00'))->create(),
        'later_today' => FollowUp::factory()->for($lead)->dueAt(Carbon::parse('2026-10-17 15:30:00'))->create(),
        'tomorrow' => FollowUp::factory()->for($lead)->dueAt(Carbon::parse('2026-10-17 17:00:00'))->create(),
        'next_month' => FollowUp::factory()->for($lead)->dueAt(Carbon::parse('2026-11-20 02:00:00'))->create(),
        'done' => FollowUp::factory()->for($lead)->completed()->create(),
    ];
}

it('lists today’s follow-ups in the agent’s timezone, overdue first', function () {
    $agent = User::factory()->create();
    $followUps = followUpsFor($agent);
    FollowUp::factory()->dueAt(now())->create();

    $this->actingAs($agent)
        ->getJson(ws($agent).'/follow-ups?'.http_build_query(['window' => 'today']))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $followUps['overdue']->id)
        ->assertJsonPath('data.0.is_overdue', true)
        ->assertJsonPath('data.1.id', $followUps['later_today']->id)
        ->assertJsonPath('data.1.is_overdue', false);
});

it('lists upcoming, open and completed follow-ups', function () {
    $agent = User::factory()->create();
    $followUps = followUpsFor($agent);
    $this->actingAs($agent);

    $this->getJson(ws($agent).'/follow-ups?'.http_build_query(['window' => 'upcoming']))
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $followUps['tomorrow']->id);

    $this->getJson(ws($agent).'/follow-ups')->assertJsonCount(4, 'data');

    $this->getJson(ws($agent).'/follow-ups?'.http_build_query(['window' => 'completed']))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $followUps['done']->id);
});

it('lists one lead’s follow-ups and links to its client', function () {
    $agent = User::factory()->create();
    followUpsFor($agent);
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    $client = Client::factory()->for(workspaceOf($agent))->create(['lead_id' => $lead->id]);
    FollowUp::factory()->for($lead)->create();

    $this->actingAs($agent)
        ->getJson(ws($agent).'/follow-ups?'.http_build_query(['lead_id' => $lead->id]))
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.lead.client_id', $client->id);
});

it('summarizes open follow-ups', function () {
    $agent = User::factory()->create();
    followUpsFor($agent);

    $this->actingAs($agent)
        ->getJson(ws($agent).'/follow-ups/summary')
        ->assertOk()
        ->assertExactJson(['data' => ['overdue' => 1, 'due_today' => 1, 'upcoming' => 1]]);
});

it('requires authentication', function () {
    $agent = User::factory()->create();
    $this->getJson(ws($agent).'/follow-ups')->assertUnauthorized();
});
