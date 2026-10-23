<?php

declare(strict_types=1);

namespace App\DataTransferObject\Auth;

use App\DataTransferObject\Data;

class TwoFactorChallengeData extends Data
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $recoveryCode = null,
    ) {}
}
