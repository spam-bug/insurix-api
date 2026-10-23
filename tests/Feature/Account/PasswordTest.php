<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('changes the password and signs out other devices', function () {
    $agent = User::factory()->create(['password' => 'old-password-123']);
    $current = $agent->createToken('Chrome on Windows');
    $agent->createToken('Safari on iPhone');

    $this->withToken($current->plainTextToken)
        ->putJson(route('api.v1.account.password.update'), [
            'current_password' => 'old-password-123',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])
        ->assertNoContent();

    expect(Hash::check('new-password-456', $agent->fresh()->password))->toBeTrue()
        ->and($agent->tokens()->pluck('id')->all())->toBe([$current->accessToken->id]);
});

it('needs the current password to change it', function () {
    $agent = User::factory()->create(['password' => 'old-password-123']);

    $this->actingAs($agent, 'sanctum')
        ->putJson(route('api.v1.account.password.update'), [
            'current_password' => 'not-it',
            'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])
        ->assertJsonValidationErrors(['current_password' => 'current password']);
});

it('emails a reset link that opens the frontend, without revealing who has an account', function () {
    Notification::fake();
    $agent = User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.password.email'), ['email' => 'ANA@example.com'])->assertOk();
    $this->postJson(route('api.v1.auth.password.email'), ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('message', 'If that email has an account, a reset link is on its way.');

    Notification::assertSentTo($agent, ResetPassword::class, function (ResetPassword $notification) use ($agent) {
        $url = $notification->toMail($agent)->actionUrl;

        return str_starts_with($url, 'http://localhost:3000/reset-password?token=') && str_contains($url, 'email=ana%40example.com');
    });
    Notification::assertCount(1);
});

it('resets the password from the link and signs out every device', function () {
    $agent = User::factory()->create(['email' => 'ana@example.com']);
    $agent->createToken('Chrome on Windows');
    $token = Password::createToken($agent);

    $this->postJson(route('api.v1.auth.password.reset'), [
        'email' => 'ana@example.com',
        'token' => $token,
        'password' => 'brand-new-pass-1',
        'password_confirmation' => 'brand-new-pass-1',
    ])->assertOk();

    expect(Hash::check('brand-new-pass-1', $agent->fresh()->password))->toBeTrue()
        ->and($agent->tokens()->count())->toBe(0);
});

it('rejects a bad reset token', function () {
    User::factory()->create(['email' => 'ana@example.com']);

    $this->postJson(route('api.v1.auth.password.reset'), [
        'email' => 'ana@example.com',
        'token' => 'made-up',
        'password' => 'brand-new-pass-1',
        'password_confirmation' => 'brand-new-pass-1',
    ])->assertJsonValidationErrors('email');
});
