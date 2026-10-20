<?php

declare(strict_types=1);

use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

it('counts every status for the agent’s own leads', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->count(2)->create();
    Lead::factory()->for($agent)->status(LeadStatus::Qualified)->create();
    Lead::factory()->status(LeadStatus::Lost)->create();

    $this->actingAs($agent)
        ->getJson(route('api.v1.leads.status-counts'))
        ->assertOk()
        ->assertExactJson(['data' => [
            'total' => 3,
            'prospect' => 2,
            'contacted' => 0,
            'meeting_scheduled' => 0,
            'qualified' => 1,
            'lost' => 0,
            'converted' => 0,
        ]]);
});

it('ignores the list filters', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->count(2)->create();

    $this->actingAs($agent)
        ->getJson(route('api.v1.leads.status-counts', ['status' => 'lost']))
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

it('requires authentication', function () {
    $this->getJson(route('api.v1.leads.status-counts'))->assertUnauthorized();
});
