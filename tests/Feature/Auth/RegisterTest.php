<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Hash;

it('registers an agent and returns an access token', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Agent',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'device_name' => 'insurix-web',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Jane Agent')
        ->assertJsonPath('data.email', 'jane@example.com')
        ->assertJsonMissingPath('data.password')
        ->assertJsonStructure(['data' => ['id', 'name', 'email', 'created_at'], 'token']);

    $user = User::where('email', 'jane@example.com')->sole();
    expect(Hash::check('secret-password', $user->password))->toBeTrue()
        ->and($user->tokens()->sole()->name)->toBe('insurix-web');
});

it('rejects invalid registration data', function (array $payload, string $invalidField) {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/v1/auth/register', array_merge([
        'name' => 'Jane Agent',
        'email' => 'jane@example.com',
        'password' => 'secret-password',
        'password_confirmation' => 'secret-password',
        'device_name' => 'insurix-web',
    ], $payload))->assertUnprocessable()->assertJsonValidationErrors($invalidField);

    expect(User::count())->toBe(1);
})->with([
    'missing name' => [['name' => ''], 'name'],
    'invalid email' => [['email' => 'not-an-email'], 'email'],
    'email already taken' => [['email' => 'taken@example.com'], 'email'],
    'unconfirmed password' => [['password_confirmation' => 'different-password'], 'password'],
    'short password' => [['password' => 'short', 'password_confirmation' => 'short'], 'password'],
    'missing device name' => [['device_name' => ''], 'device_name'],
]);
