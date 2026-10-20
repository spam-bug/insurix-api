<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\User;

it('lists only the agent’s own clients, newest first', function () {
    $agent = User::factory()->create();
    $older = Client::factory()->for($agent)->create(['created_at' => now()->subDay()]);
    $newer = Client::factory()->for($agent)->create();
    Client::factory()->create();

    $this->actingAs($agent)
        ->getJson('/api/v1/clients')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('data.0.id', $newer->id)
        ->assertJsonPath('data.1.id', $older->id)
        ->assertJsonPath('meta.total', 2);
});

it('searches clients by name, email or phone', function () {
    $agent = User::factory()->create();
    $match = Client::factory()->for($agent)->create(['name' => 'Teodora Alonso']);
    Client::factory()->for($agent)->create(['name' => 'Someone Else']);

    $this->actingAs($agent)
        ->getJson('/api/v1/clients?'.http_build_query(['search' => 'teodora']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $match->id);
});

it('shows a client', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create(['birthdate' => '1990-02-03']);

    $this->actingAs($agent)
        ->getJson("/api/v1/clients/{$client->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $client->id)
        ->assertJsonPath('data.birthdate', '1990-02-03');
});

it('updates a client', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->putJson("/api/v1/clients/{$client->id}", [
            'name' => 'Teodora Alonso',
            'email' => 'TEODORA@example.com',
            'phone' => null,
            'civil_status' => 'widowed',
            'occupation' => 'Teacher',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Teodora Alonso')
        ->assertJsonPath('data.email', 'teodora@example.com')
        ->assertJsonPath('data.phone', null)
        ->assertJsonPath('data.civil_status.value', 'widowed')
        ->assertJsonPath('data.birthdate', null);
});

it('validates client updates', function () {
    $agent = User::factory()->create();
    $client = Client::factory()->for($agent)->create();

    $this->actingAs($agent)
        ->putJson("/api/v1/clients/{$client->id}", ['email' => 'not-an-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);
});

it('forbids another agent’s client', function () {
    $client = Client::factory()->create();
    $this->actingAs(User::factory()->create());

    $this->getJson("/api/v1/clients/{$client->id}")->assertForbidden();
    $this->putJson("/api/v1/clients/{$client->id}", ['name' => 'Taken'])->assertForbidden();
});

it('requires authentication', function () {
    $this->getJson('/api/v1/clients')->assertUnauthorized();
});
