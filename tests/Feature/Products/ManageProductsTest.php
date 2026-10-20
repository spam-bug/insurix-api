<?php

declare(strict_types=1);

use App\Enums\PolicyStatus;
use App\Models\Client;
use App\Models\Policy;
use App\Models\Product;
use App\Models\User;

/**
 * @return array<string, mixed>
 */
function productPayload(array $overrides = []): array
{
    return [
        'name' => 'Sun FitandWell',
        'type' => 'health',
        'description' => 'Health plan with critical illness cover.',
        'commission_rate' => '35',
        'payment_frequency' => 'monthly',
        ...$overrides,
    ];
}

it('adds a product to the agent’s catalog', function () {
    $agent = User::factory()->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.products.store'), productPayload())
        ->assertCreated()
        ->assertJsonPath('data.name', 'Sun FitandWell')
        ->assertJsonPath('data.type', ['value' => 'health', 'label' => 'Health'])
        ->assertJsonPath('data.commission_rate', 35)
        ->assertJsonPath('data.payment_frequency.value', 'monthly')
        ->assertJsonPath('data.is_active', true);

    expect(Product::sole()->user_id)->toBe($agent->id);
});

it('keeps product names unique within a catalog', function () {
    $agent = User::factory()->create();
    Product::factory()->for($agent)->create(['name' => 'Sun FitandWell']);
    Product::factory()->create(['name' => 'Sun MaxiLink Prime']);
    $this->actingAs($agent);

    $this->postJson(route('api.v1.products.store'), productPayload())->assertJsonValidationErrors(['name' => 'already have']);
    $this->postJson(route('api.v1.products.store'), productPayload(['name' => 'Sun MaxiLink Prime']))->assertCreated();
});

it('validates a product', function () {
    $this->actingAs(User::factory()->create())
        ->postJson(route('api.v1.products.store'), ['commission_rate' => '150', 'type' => 'pet'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'type', 'commission_rate']);
});

it('lists the catalog with how many clients hold each product', function () {
    $agent = User::factory()->create();
    $sold = Product::factory()->for($agent)->create(['name' => 'AXA Global Health']);
    Product::factory()->for($agent)->inactive()->create(['name' => 'Philam Vitality Plan']);
    Product::factory()->create();
    $client = Client::factory()->for($agent)->create();
    Policy::factory()->for($client)->count(2)->create(['product_id' => $sold->id]);
    Policy::factory()->for($client)->status(PolicyStatus::Cancelled)->create(['product_id' => $sold->id]);
    $this->actingAs($agent);

    $this->getJson(route('api.v1.products.index'))
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $sold->id)
        ->assertJsonPath('data.0.policies_count', 2);

    $this->getJson(route('api.v1.products.index', ['active' => 1]))->assertJsonCount(1, 'data');
    $this->getJson(route('api.v1.products.index', ['search' => 'philam']))->assertJsonCount(1, 'data');
});

it('updates a product and can deactivate it', function () {
    $agent = User::factory()->create();
    $product = Product::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->putJson(route('api.v1.products.update', $product), productPayload(['commission_rate' => '40', 'is_active' => false]))
        ->assertOk()
        ->assertJsonPath('data.commission_rate', 40)
        ->assertJsonPath('data.is_active', false);
});

it('deletes an unused product but not one clients hold', function () {
    $agent = User::factory()->create();
    $unused = Product::factory()->for($agent)->create();
    $sold = Product::factory()->for($agent)->create();
    Policy::factory()->for(Client::factory()->for($agent))->create(['product_id' => $sold->id]);
    $this->actingAs($agent);

    $this->deleteJson(route('api.v1.products.destroy', $unused))->assertNoContent();
    $this->deleteJson(route('api.v1.products.destroy', $sold))->assertJsonValidationErrors('product');

    expect(Product::count())->toBe(1);
});

it('forbids another agent’s products', function () {
    $product = Product::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->getJson(route('api.v1.products.show', $product))->assertForbidden();
    $this->putJson(route('api.v1.products.update', $product), productPayload())->assertForbidden();
    $this->deleteJson(route('api.v1.products.destroy', $product))->assertForbidden();
});

it('records a policy from a catalog product', function () {
    $agent = User::factory()->create();
    $product = Product::factory()->for($agent)->create(['name' => 'PRULink Assurance', 'type' => 'life']);
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->postJson(route('api.v1.clients.policies.store', $client), [
            'product_id' => $product->id,
            'status' => 'pending',
            'premium' => '5000',
            'coverage_amount' => '1000000',
            'payment_frequency' => 'quarterly',
            'commission_rate' => '25',
        ])
        ->assertCreated()
        ->assertJsonPath('data.product_id', $product->id)
        ->assertJsonPath('data.product_name', 'PRULink Assurance')
        ->assertJsonPath('data.type.value', 'life')
        ->assertJsonPath('data.coverage_amount', 1000000);

    $this->getJson(route('api.v1.policies.index', ['product_id' => $product->id]))->assertJsonCount(1, 'data');
});

it('still records a policy without a catalog product', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
    $this->actingAs($agent);

    $this->postJson(route('api.v1.clients.policies.store', $client), ['status' => 'pending', 'premium' => '100', 'payment_frequency' => 'annual', 'commission_rate' => '10'])
        ->assertJsonValidationErrors(['product_name', 'type']);
});

it('refuses another agent’s or an inactive product for a new policy', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();
    $payload = ['status' => 'pending', 'premium' => '100', 'payment_frequency' => 'annual', 'commission_rate' => '10'];
    $this->actingAs($agent);

    $this->postJson(route('api.v1.clients.policies.store', $client), [...$payload, 'product_id' => Product::factory()->create()->id])
        ->assertJsonValidationErrors('product_id');

    $inactive = Product::factory()->for($agent)->inactive()->create();
    $this->postJson(route('api.v1.clients.policies.store', $client), [...$payload, 'product_id' => $inactive->id])
        ->assertJsonValidationErrors(['product_id' => 'inactive']);
});

it('lets a policy keep a product that has since been deactivated', function () {
    $agent = User::factory()->create();
    $product = Product::factory()->for($agent)->inactive()->create();
    $policy = Policy::factory()->for(Client::factory()->for($agent))->create(['product_id' => $product->id]);

    $this->actingAs($agent)
        ->putJson(route('api.v1.policies.update', $policy), [
            'product_id' => $product->id,
            'status' => 'in_force',
            'premium' => '2500',
            'payment_frequency' => 'monthly',
            'commission_rate' => '30',
        ])
        ->assertOk();
});
