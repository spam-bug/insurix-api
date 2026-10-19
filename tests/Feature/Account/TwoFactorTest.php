<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Fortify;
use PragmaRX\Google2FA\Google2FA;

function currentCode(User $user): string
{
    return app(Google2FA::class)->getCurrentOtp(Fortify::currentEncrypter()->decrypt($user->fresh()->two_factor_secret));
}

/**
 * An agent with two-factor already on, and their password.
 */
function agentWithTwoFactor(): User
{
    $agent = User::factory()->create(['email' => 'ana@example.com', 'password' => 'secret-password-1']);
    test()->actingAs($agent)->postJson(route('api.v1.account.two-factor.store'), ['password' => 'secret-password-1'])->assertOk();
    test()->postJson(route('api.v1.account.two-factor.confirm'), ['code' => currentCode($agent)])->assertOk();
    app('auth')->forgetGuards();
    // Fortify refuses a code it has already accepted; clear that record so sign-in can use
    // the code from the same 30-second window.
    Cache::flush();

    return $agent->fresh();
}

it('sets up two-factor with a QR code and turns it on once a code is confirmed', function () {
    $agent = User::factory()->create(['password' => 'secret-password-1']);
    $this->actingAs($agent);

    $this->postJson(route('api.v1.account.two-factor.store'), ['password' => 'wrong'])->assertJsonValidationErrors('password');

    $setup = $this->postJson(route('api.v1.account.two-factor.store'), ['password' => 'secret-password-1'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['qr_code_svg', 'secret']]);
    expect($setup->json('data.qr_code_svg'))->toContain('<svg')
        ->and($agent->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();

    $this->postJson(route('api.v1.account.two-factor.confirm'), ['code' => '000000'])->assertJsonValidationErrors(['code' => 'didn’t match']);

    $confirmed = $this->postJson(route('api.v1.account.two-factor.confirm'), ['code' => currentCode($agent)])->assertOk();
    expect($confirmed->json('data.recovery_codes'))->toHaveCount(8)
        ->and($agent->fresh()->hasEnabledTwoFactorAuthentication())->toBeTrue();

    $this->getJson(route('api.v1.auth.user'))->assertJsonPath('data.two_factor_enabled', true);
});

it('asks for a code after the password when two-factor is on', function () {
    $agent = agentWithTwoFactor();

    $login = $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'secret-password-1', 'device_name' => 'Chrome on Windows'])
        ->assertOk()
        ->assertJsonPath('two_factor', true)
        ->assertJsonMissingPath('token');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), ['challenge' => $login->json('challenge'), 'code' => '000000'])
        ->assertJsonValidationErrors('code');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), ['challenge' => $login->json('challenge'), 'code' => currentCode($agent)])
        ->assertOk()
        ->assertJsonPath('data.id', $agent->id)
        ->assertJsonStructure(['token']);

    expect($agent->tokens()->sole()->name)->toBe('Chrome on Windows');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), ['challenge' => $login->json('challenge'), 'code' => currentCode($agent)])
        ->assertJsonValidationErrors(['code' => 'expired']);
});

it('accepts each recovery code once', function () {
    $agent = agentWithTwoFactor();
    $recoveryCode = $agent->recoveryCodes()[0];
    $login = fn () => $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'secret-password-1', 'device_name' => 'web'])->json('challenge');

    $this->postJson(route('api.v1.auth.two-factor-challenge'), ['challenge' => $login(), 'recovery_code' => $recoveryCode])->assertOk();
    $this->postJson(route('api.v1.auth.two-factor-challenge'), ['challenge' => $login(), 'recovery_code' => $recoveryCode])
        ->assertJsonValidationErrors('recovery_code');
});

it('gives up on a challenge after too many wrong codes', function () {
    agentWithTwoFactor();
    $challenge = $this->postJson(route('api.v1.auth.login'), ['email' => 'ana@example.com', 'password' => 'secret-password-1', 'device_name' => 'web'])->json('challenge');

    foreach (range(1, 5) as $attempt) {
        $this->postJson(route('api.v1.auth.two-factor-challenge'), ['challenge' => $challenge, 'code' => '000000']);
    }

    $this->postJson(route('api.v1.auth.two-factor-challenge'), ['challenge' => $challenge, 'code' => '000000'])
        ->assertJsonValidationErrors(['code' => 'Too many']);
});

it('turns two-factor off and regenerates recovery codes with the password', function () {
    $agent = agentWithTwoFactor();
    $oldCodes = $agent->recoveryCodes();
    $this->actingAs($agent);

    $codes = $this->postJson(route('api.v1.account.two-factor.recovery-codes'), ['password' => 'secret-password-1'])->assertOk()->json('data.recovery_codes');
    expect($codes)->toHaveCount(8)->not->toBe($oldCodes);

    $this->deleteJson(route('api.v1.account.two-factor.destroy'), ['password' => 'wrong'])->assertJsonValidationErrors('password');
    $this->deleteJson(route('api.v1.account.two-factor.destroy'), ['password' => 'secret-password-1'])->assertNoContent();

    expect($agent->fresh()->hasEnabledTwoFactorAuthentication())->toBeFalse();
});
