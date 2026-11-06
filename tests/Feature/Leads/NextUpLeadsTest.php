<?php

declare(strict_types=1);

use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;

it('gives each lead its next follow-up and last contact', function () {
    $agent = User::factory()->create();
    $lead = Lead::factory()->for(workspaceOf($agent))->create();
    FollowUp::factory()->for($lead)->dueAt(now()->addDays(5))->create();
    FollowUp::factory()->for($lead)->dueAt(now()->addDay())->create();
    FollowUp::factory()->for($lead)->dueAt(now()->subDay())->completed()->create();
    LeadActivity::factory()->for($lead)->create(['type' => LeadActivityType::Note, 'created_at' => now()->subDays(2)]);
    LeadActivity::factory()->for($lead)->create(['type' => LeadActivityType::Updated, 'created_at' => now()]);

    $this->actingAs($agent)->getJson(ws($agent).'/leads')
        ->assertJsonPath('data.0.next_follow_up_at', now()->addDay()->startOfSecond()->toIso8601String())
        ->assertJsonPath('data.0.last_contacted_at', now()->subDays(2)->startOfSecond()->toIso8601String());
});

it('lists overdue leads first, then quiet hot leads, then uncontacted form replies', function () {
    $this->freezeSecond();
    $agent = User::factory()->create();
    $workspace = workspaceOf($agent);

    $formReply = Lead::factory()->for($workspace)->create(['name' => 'Form reply', 'source' => LeadSource::ShareableForm]);
    $quietHot = Lead::factory()->for($workspace)->create(['name' => 'Quiet hot', 'score' => 85, 'created_at' => now()->subDays(10)]);
    $overdue = Lead::factory()->for($workspace)->create(['name' => 'Overdue']);
    FollowUp::factory()->for($overdue)->dueAt(now()->subHour())->create();

    $recentlyContactedHot = Lead::factory()->for($workspace)->create(['score' => 90, 'created_at' => now()->subDays(10)]);
    LeadActivity::factory()->for($recentlyContactedHot)->create(['type' => LeadActivityType::Note, 'created_at' => now()->subDay()]);
    $plannedHot = Lead::factory()->for($workspace)->create(['score' => 90, 'created_at' => now()->subDays(10)]);
    FollowUp::factory()->for($plannedHot)->dueAt(now()->addDay())->create();
    Lead::factory()->for($workspace)->status(LeadStatus::Lost)->create(['source' => LeadSource::ShareableForm]);
    Lead::factory()->create(['source' => LeadSource::ShareableForm]);

    $this->actingAs($agent)->getJson(ws($agent).'/leads/next-up')
        ->assertOk()
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.reason', 'overdue')
        ->assertJsonPath('data.0.lead.id', $overdue->id)
        ->assertJsonPath('data.1.reason', 'hot_quiet')
        ->assertJsonPath('data.1.lead.id', $quietHot->id)
        ->assertJsonPath('data.2.reason', 'form_reply')
        ->assertJsonPath('data.2.lead.id', $formReply->id);
});

it('shows at most five leads', function () {
    $agent = User::factory()->create();
    Lead::factory()->for(workspaceOf($agent))->count(7)->create(['source' => LeadSource::ShareableForm]);

    $this->actingAs($agent)->getJson(ws($agent).'/leads/next-up')->assertJsonCount(5, 'data');
});
