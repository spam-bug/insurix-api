<?php

declare(strict_types=1);

use App\Models\User;

it('returns the agent that owns the token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('insurix-web')->plainTextToken;

    $this->withToken($token)
        ->getJson('/api/v1/auth/user')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.email', $user->email);
});

it('revokes only the current token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('insurix-web')->plainTextToken;
    $user->createToken('other-device');

    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertNoContent();

    expect($user->tokens()->pluck('name')->all())->toBe(['other-device']);

    $this->app['auth']->forgetGuards();
    $this->withToken($token)->getJson('/api/v1/auth/user')->assertUnauthorized();
});

it('rejects guests', function (string $method, string $url) {
    $this->json($method, $url)->assertUnauthorized();
})->with([
    'current user' => ['GET', '/api/v1/auth/user'],
    'logout' => ['POST', '/api/v1/auth/logout'],
]);
