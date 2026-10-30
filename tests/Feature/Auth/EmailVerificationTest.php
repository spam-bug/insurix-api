<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

/** The API path inside the link the email sends, as the frontend posts it back. */
function verificationPath(User $user): string
{
    $link = null;
    Notification::assertSentTo($user, VerifyEmailNotification::class, function (VerifyEmailNotification $notification) use ($user, &$link) {
        parse_str((string) parse_url($notification->toMail($user)->actionUrl, PHP_URL_QUERY), $query);
        $link = $query['link'];

        return true;
    });

    return $link;
}

it('sends a confirmation link on sign-up that verifies the email, even signed out', function () {
    Notification::fake();
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Ana', 'email' => 'ana@example.com', 'password' => 'password-123', 'password_confirmation' => 'password-123',
    ])->assertSuccessful()->assertJsonPath('data.email_verified_at', null);
    $user = User::where('email', 'ana@example.com')->sole();
    $path = verificationPath($user);

    $this->app['auth']->forgetGuards();
    $this->postJson($path)->assertNoContent();

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects links with a bad signature or for another address', function () {
    $user = User::factory()->unverified()->create();
    $signed = fn (string $hash) => URL::temporarySignedRoute('verification.verify', now()->addDay(), ['user' => $user->id, 'hash' => $hash], absolute: false);

    $this->postJson("/api/v1/auth/email-verification/{$user->id}/".sha1($user->email))->assertForbidden();
    $this->postJson($signed(sha1('old@example.com')))->assertForbidden();
    $this->postJson($signed(sha1($user->email)).'x')->assertForbidden();

    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
});

it('resends the link, and asks again after the email changes', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->actingAs($user)->postJson('/api/v1/auth/email-verification-notification')->assertNoContent();
    Notification::assertNothingSent();

    $this->putJson('/api/v1/account/profile', ['name' => $user->name, 'email' => 'new@example.com'])->assertOk();
    expect($user->refresh()->hasVerifiedEmail())->toBeFalse();
    Notification::assertSentTo($user, VerifyEmailNotification::class);

    $this->postJson('/api/v1/auth/email-verification-notification')->assertNoContent();
    Notification::assertSentToTimes($user, VerifyEmailNotification::class, 2);
});
