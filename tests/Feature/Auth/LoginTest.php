<?php

declare(strict_types=1);

use App\Actions\Auth\AuthenticateUser;
use App\Models\User;

it('logs an agent in and returns an access token', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson(route('api.v1.auth.login'), [
        'email' => 'jane@example.com',
        'password' => 'password',
        'device_name' => 'insurix-web',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonStructure(['data' => ['id', 'name', 'email'], 'token']);

    expect($user->tokens()->sole()->name)->toBe('insurix-web');
});

it('rejects wrong credentials without issuing a token', function (string $email, string $password) {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson(route('api.v1.auth.login'), [
        'email' => $email,
        'password' => $password,
        'device_name' => 'insurix-web',
    ])->assertUnprocessable()->assertJsonValidationErrors(['email' => trans('auth.failed')]);

    expect($user->tokens()->count())->toBe(0);
})->with([
    'wrong password' => ['jane@example.com', 'wrong-password'],
    'unknown email' => ['nobody@example.com', 'password'],
]);

it('locks out repeated failed attempts', function () {
    User::factory()->create(['email' => 'jane@example.com']);
    $credentials = ['email' => 'jane@example.com', 'password' => 'wrong-password', 'device_name' => 'insurix-web'];

    foreach (range(1, AuthenticateUser::MAX_ATTEMPTS) as $attempt) {
        $this->postJson(route('api.v1.auth.login'), $credentials)->assertUnprocessable();
    }

    $this->postJson(route('api.v1.auth.login'), [...$credentials, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', fn (string $message) => str_contains($message, 'Too many login attempts'));
});

it('requires email, password and device name', function () {
    $this->postJson(route('api.v1.auth.login'), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password', 'device_name']);
});
