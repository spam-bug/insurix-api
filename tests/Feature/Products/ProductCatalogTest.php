<?php

declare(strict_types=1);

use App\Enums\PaymentFrequency;
use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\Policy;
use App\Models\Product;
use App\Models\User;

function policyFor(User $agent, array $attributes = []): Policy
{
    return Policy::factory()->for(Client::factory()->for(workspaceOf($agent)))->create([
        'payment_frequency' => PaymentFrequency::Annual,
        'commission_rate' => 20,
        ...$attributes,
    ]);
}

it('lists products with their policies, premium and commission in force, and sorts by them', function () {
    $agent = User::factory()->create();
    $small = Product::factory()->for(workspaceOf($agent))->create(['name' => 'Alpha']);
    $big = Product::factory()->for(workspaceOf($agent))->create(['name' => 'Beta']);
    policyFor($agent, ['product_id' => $small->id, 'premium' => 1000]);
    policyFor($agent, ['product_id' => $small->id, 'premium' => 1000]);
    policyFor($agent, ['product_id' => $big->id, 'premium' => 50000]);
    policyFor($agent, ['product_id' => $big->id, 'premium' => 99000, 'status' => PolicyStatus::Lapsed]);

    $this->actingAs($agent);
    $this->getJson(ws($agent).'/products')
        ->assertJsonPath('data.1.name', 'Beta')
        ->assertJsonPath('data.1.in_force_policies', 1)
        ->assertJsonPath('data.1.annual_premium', 50000)
        ->assertJsonPath('data.1.commission', 10000)
        ->assertJsonPath('data.1.policies_count', 2);

    expect($this->getJson(ws($agent).'/products?sort=most_sold')->json('data.*.name'))->toBe(['Alpha', 'Beta'])
        ->and($this->getJson(ws($agent).'/products?sort=top_earning')->json('data.*.name'))->toBe(['Beta', 'Alpha'])
        ->and($this->getJson(ws($agent)."/products/{$big->id}")->json('data.commission'))->toEqual(10000);
});

it('summarizes the catalog', function () {
    $agent = User::factory()->create();
    $earner = Product::factory()->for(workspaceOf($agent))->create(['name' => 'PRULink']);
    Product::factory()->for(workspaceOf($agent))->create(['is_active' => false]);
    policyFor($agent, ['product_id' => $earner->id, 'premium' => 10000]);
    policyFor($agent, ['product_id' => null, 'product_name' => 'Typed plan']);

    $this->actingAs($agent)->getJson(ws($agent).'/products/summary')
        ->assertExactJson(['data' => [
            'active' => 1,
            'inactive' => 1,
            'top_earner' => ['id' => $earner->id, 'name' => 'PRULink', 'commission' => 2000],
            'off_catalog_policies' => 1,
        ]]);
});

it('suggests typed-in product names, grouped ignoring case, and flags ones already in the catalog', function () {
    $agent = User::factory()->create();
    Product::factory()->for(workspaceOf($agent))->create(['name' => 'Health Max']);
    policyFor($agent, ['product_id' => null, 'product_name' => 'Life Plus', 'commission_rate' => 20]);
    policyFor($agent, ['product_id' => null, 'product_name' => ' life plus ', 'commission_rate' => 30]);
    policyFor($agent, ['product_id' => null, 'product_name' => 'health max']);

    $suggestions = $this->actingAs($agent)->getJson(ws($agent).'/products/suggestions')->json('data');

    expect($suggestions)->toHaveCount(2)
        ->and($suggestions[0])->toMatchArray(['policies' => 2, 'commission_rate' => 25, 'in_catalog' => false])
        ->and(mb_strtolower($suggestions[0]['name']))->toBe('life plus')
        ->and($suggestions[1]['in_catalog'])->toBeTrue();
});

it('adds a suggested product and links its policies, or links to the product already there', function () {
    $agent = User::factory()->create();
    $existing = Product::factory()->for(workspaceOf($agent))->create(['name' => 'Health Max']);
    $first = policyFor($agent, ['product_id' => null, 'product_name' => 'Life Plus', 'commission_rate' => 20]);
    $second = policyFor($agent, ['product_id' => null, 'product_name' => 'LIFE PLUS', 'commission_rate' => 30]);
    $typedExisting = policyFor($agent, ['product_id' => null, 'product_name' => 'health max']);

    $this->actingAs($agent)->postJson(ws($agent).'/products/suggestions', ['name' => 'Life Plus'])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Life Plus')
        ->assertJsonPath('data.commission_rate', 25)
        ->assertJsonPath('linked', 2);

    expect($first->fresh()->product_id)->not->toBeNull()->toBe($second->fresh()->product_id);

    $this->postJson(ws($agent).'/products/suggestions', ['name' => 'health max'])
        ->assertJsonPath('data.id', $existing->id)
        ->assertJsonPath('linked', 1);
    expect($typedExisting->fresh()->product_id)->toBe($existing->id)
        ->and(Product::count())->toBe(2);
});
