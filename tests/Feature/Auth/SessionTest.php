<?php

declare(strict_types=1);

use App\Models\User;

it('returns the signed-in agent', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/auth/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('signs out and ends the session', function () {
    $user = User::factory()->create();
    $this->postJson('/api/v1/auth/login', ['email' => $user->email, 'password' => 'password'])->assertOk();

    $this->postJson('/api/v1/auth/logout')->assertNoContent();

    $this->assertGuest('web');
    $this->app['auth']->forgetGuards();
    $this->getJson('/api/v1/auth/user')->assertUnauthorized();
});

it('rejects guests', function (string $method, string $url) {
    $this->json($method, $url)->assertUnauthorized();
})->with([
    'current user' => ['GET', '/api/v1/auth/user'],
    'logout' => ['POST', '/api/v1/auth/logout'],
]);
