<?php

declare(strict_types=1);

use App\Enums\LeadInterest;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

it('lists only the agent’s own leads, newest first', function () {
    $agent = User::factory()->create();
    $older = Lead::factory()->for(workspaceOf($agent))->create(['created_at' => now()->subDay()]);
    $newer = Lead::factory()->for(workspaceOf($agent))->create();
    Lead::factory()->create();

    $this->actingAs($agent)
        ->getJson(ws($agent).'/leads')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('meta.total', 2)
        ->assertJsonStructure(['data' => [['id', 'name', 'email', 'phone', 'address', 'source' => ['value', 'label'], 'interest' => ['value', 'label'], 'status' => ['value', 'label'], 'notes', 'created_at']]]);
});

it('filters leads by search, status and interest', function (array $query, array $expectedNames) {
    $agent = User::factory()->create();
    Lead::factory()->for(workspaceOf($agent))->create(['name' => 'Ana Reyes', 'email' => 'ana@example.com', 'interest' => LeadInterest::Life]);
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Qualified)->create(['name' => 'Ben Cruz', 'phone' => '0917 555 0100', 'interest' => LeadInterest::Auto]);
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Lost)->create(['name' => 'Carla Diaz', 'interest' => LeadInterest::Health]);

    $names = $this->actingAs($agent)
        ->getJson(ws($agent).'/leads?'.http_build_query($query))
        ->assertOk()
        ->json('data.*.name');

    expect($names)->toEqualCanonicalizing($expectedNames);
})->with([
    'search by name' => [['search' => 'ana'], ['Ana Reyes']],
    'search by phone' => [['search' => '555 0100'], ['Ben Cruz']],
    'status' => [['status' => 'qualified'], ['Ben Cruz']],
    'interest' => [['interest' => 'health'], ['Carla Diaz']],
    'open leads only' => [['open' => 1], ['Ana Reyes', 'Ben Cruz']],
    'a status wins over open' => [['open' => 1, 'status' => 'lost'], ['Carla Diaz']],
    'no match' => [['search' => 'zzz'], []],
]);

it('leaves clients out of the open leads', function () {
    $agent = User::factory()->create();
    Lead::factory()->for(workspaceOf($agent))->create(['name' => 'Ana Reyes']);
    Lead::factory()->for(workspaceOf($agent))->status(LeadStatus::Converted)->create(['name' => 'Ben Cruz']);

    $names = $this->actingAs($agent)
        ->getJson(ws($agent).'/leads?'.http_build_query(['open' => 1]))
        ->assertOk()
        ->json('data.*.name');

    expect($names)->toBe(['Ana Reyes']);
});

it('paginates leads', function () {
    $agent = User::factory()->create();
    Lead::factory()->for(workspaceOf($agent))->count(12)->create();

    $this->actingAs($agent)
        ->getJson(ws($agent).'/leads?'.http_build_query(['per_page' => 5, 'page' => 3]))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.current_page', 3)
        ->assertJsonPath('meta.last_page', 3);
});

it('rejects invalid filters', function () {
    $this->actingAs($agent = User::factory()->create())
        ->getJson(ws($agent).'/leads?'.http_build_query(['status' => 'client', 'per_page' => 500]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status', 'per_page']);
});

it('requires authentication', function () {
    $agent = User::factory()->create();
    $this->getJson(ws($agent).'/leads')->assertUnauthorized();
});
