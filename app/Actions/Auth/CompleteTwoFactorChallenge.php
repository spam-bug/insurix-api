<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\DataTransferObject\Auth\TwoFactorChallengeData;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;

class CompleteTwoFactorChallenge
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(private TwoFactorAuthenticationProvider $provider) {}

    /**
     * @throws ValidationException
     */
    public function handle(TwoFactorChallengeData $data): User
    {
        $challenge = session('two_factor');
        $user = $challenge && $challenge['expires_at'] > now()->timestamp ? User::find($challenge['user_id']) : null;

        if (! $user) {
            session()->forget('two_factor');

            throw ValidationException::withMessages(['code' => 'This sign-in has expired. Enter your email and password again.']);
        }

        $throttleKey = "two-factor:{$challenge['id']}";

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            session()->forget('two_factor');

            throw ValidationException::withMessages(['code' => 'Too many wrong codes. Enter your email and password again.']);
        }

        $code = $data->code === null ? null : preg_replace('/\s+/', '', $data->code);
        $recoveryCode = $data->recoveryCode === null ? null : trim($data->recoveryCode);

        if (! $this->passes($user, $code, $recoveryCode)) {
            RateLimiter::hit($throttleKey, StartTwoFactorChallenge::TTL_MINUTES * 60);

            throw ValidationException::withMessages([$recoveryCode ? 'recovery_code' : 'code' => 'That code didn’t work. Try again.']);
        }

        RateLimiter::clear($throttleKey);
        session()->forget('two_factor');
        Auth::guard('web')->login($user);
        session()->regenerate();

        return $user;
    }

    private function passes(User $user, ?string $code, ?string $recoveryCode): bool
    {
        if ($recoveryCode) {
            $match = collect($user->recoveryCodes())->first(fn (string $saved) => hash_equals($saved, $recoveryCode));

            if (! $match) {
                return false;
            }

            $user->replaceRecoveryCode($match);

            return true;
        }

        return $code !== null && $this->provider->verify(Fortify::currentEncrypter()->decrypt($user->two_factor_secret), $code);
    }
}
