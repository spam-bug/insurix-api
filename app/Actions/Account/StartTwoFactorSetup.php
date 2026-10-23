<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Fortify;

class StartTwoFactorSetup
{
    public function __construct(private EnableTwoFactorAuthentication $enableTwoFactorAuthentication) {}

    /**
     * Creates a new authenticator secret for the agent to scan. Two-factor isn't on until they
     * confirm a code from their app, so starting again simply replaces the unconfirmed secret.
     *
     * @return array{qr_code_svg: string, secret: string}
     *
     * @throws ValidationException
     */
    public function handle(User $agent): array
    {
        if ($agent->hasEnabledTwoFactorAuthentication()) {
            throw ValidationException::withMessages(['two_factor' => 'Two-factor authentication is already on.']);
        }

        DB::transaction(function () use ($agent) {
            ($this->enableTwoFactorAuthentication)($agent, force: true);
            $agent->forceFill(['two_factor_confirmed_at' => null])->save();
        });

        return [
            'qr_code_svg' => $agent->twoFactorQrCodeSvg(),
            'secret' => Fortify::currentEncrypter()->decrypt($agent->two_factor_secret),
        ];
    }
}
