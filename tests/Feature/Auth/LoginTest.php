<?php

declare(strict_types=1);

use App\Actions\Auth\AuthenticateUser;
use App\Models\User;

it('signs an agent in', function () {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonStructure(['data' => ['id', 'name', 'email']]);

    $this->assertAuthenticatedAs($user, 'web');
});

it('rejects wrong credentials without signing in', function (string $email, string $password) {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => $email,
        'password' => $password,
    ])->assertUnprocessable()->assertJsonValidationErrors(['email' => trans('auth.failed')]);

    $this->assertGuest('web');
})->with([
    'wrong password' => ['jane@example.com', 'wrong-password'],
    'unknown email' => ['nobody@example.com', 'password'],
]);

it('locks out repeated failed attempts', function () {
    User::factory()->create(['email' => 'jane@example.com']);
    $credentials = ['email' => 'jane@example.com', 'password' => 'wrong-password'];

    foreach (range(1, AuthenticateUser::MAX_ATTEMPTS) as $attempt) {
        $this->postJson('/api/v1/auth/login', $credentials)->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/login', [...$credentials, 'password' => 'password'])
        ->assertUnprocessable()
        ->assertJsonPath('errors.email.0', fn (string $message) => str_contains($message, 'Too many login attempts'));
});

it('requires email and password', function () {
    $this->postJson('/api/v1/auth/login', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);
});
