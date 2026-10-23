<?php

declare(strict_types=1);

use App\Enums\LeadActivityType;
use App\Enums\LeadSource;
use App\Enums\LeadStatus;
use App\Models\Lead;
use App\Models\User;

it('sorts leads', function (string $sort, array $expectedNames) {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->create(['name' => 'Bea', 'created_at' => now()->subDays(2)]);
    Lead::factory()->for($agent)->create(['name' => 'Ana', 'created_at' => now()->subDay()]);
    Lead::factory()->for($agent)->create(['name' => 'Cyd', 'created_at' => now()]);

    $names = $this->actingAs($agent)->getJson('/api/v1/leads?'.http_build_query(['sort' => $sort]))->assertOk()->json('data.*.name');

    expect($names)->toBe($expectedNames);
})->with([
    'latest' => ['latest', ['Cyd', 'Ana', 'Bea']],
    'oldest' => ['oldest', ['Bea', 'Ana', 'Cyd']],
    'name a-z' => ['name_asc', ['Ana', 'Bea', 'Cyd']],
    'name z-a' => ['name_desc', ['Cyd', 'Bea', 'Ana']],
]);

it('filters leads by source', function () {
    $agent = User::factory()->create();
    Lead::factory()->for($agent)->create(['name' => 'Referred', 'source' => LeadSource::Referral]);
    Lead::factory()->for($agent)->create(['name' => 'Walk-in', 'source' => LeadSource::Manual]);

    $this->actingAs($agent)
        ->getJson('/api/v1/leads?'.http_build_query(['source' => 'referral']))
        ->assertOk()
        ->assertJsonPath('data.*.name', ['Referred']);
});

it('changes the status of several leads at once, logging each change', function () {
    $agent = User::factory()->create();
    [$first, $second] = Lead::factory()->for($agent)->count(2)->create();
    $alreadyQualified = Lead::factory()->for($agent)->status(LeadStatus::Qualified)->create();
    $untouched = Lead::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson('/api/v1/leads/bulk/status', ['ids' => [$first->id, $second->id, $alreadyQualified->id], 'status' => 'qualified'])
        ->assertOk()
        ->assertJson(['updated' => 2]);

    expect($first->fresh()->status)->toBe(LeadStatus::Qualified)
        ->and($second->fresh()->status)->toBe(LeadStatus::Qualified)
        ->and($untouched->fresh()->status)->toBe(LeadStatus::Prospect)
        ->and($first->activities()->sole()->type)->toBe(LeadActivityType::StatusChanged)
        ->and($alreadyQualified->activities()->count())->toBe(0);
});

it('deletes several leads at once', function () {
    $agent = User::factory()->create();
    [$first, $second, $kept] = Lead::factory()->for($agent)->count(3)->create();

    $this->actingAs($agent)
        ->postJson('/api/v1/leads/bulk/delete', ['ids' => [$first->id, $second->id]])
        ->assertOk()
        ->assertJson(['deleted' => 2]);

    $this->assertModelMissing($first);
    $this->assertModelMissing($second);
    $this->assertModelExists($kept);
});

it('rejects bulk changes that include another agent’s lead', function (string $url, array $extra) {
    $agent = User::factory()->create();
    $own = Lead::factory()->for($agent)->create();
    $foreign = Lead::factory()->create();

    $this->actingAs($agent)
        ->postJson($url, ['ids' => [$own->id, $foreign->id], ...$extra])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ids.1');

    $this->assertModelExists($own);
    $this->assertModelExists($foreign);
    expect($own->fresh()->status)->toBe(LeadStatus::Prospect);
})->with([
    'status' => ['/api/v1/leads/bulk/status', ['status' => 'lost']],
    'delete' => ['/api/v1/leads/bulk/delete', []],
]);

it('validates bulk requests', function () {
    $this->actingAs(User::factory()->create())
        ->postJson('/api/v1/leads/bulk/status', ['ids' => []])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ids', 'status']);
});
