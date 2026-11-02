<?php

declare(strict_types=1);

namespace App\DataTransferObject\Staff;

use App\DataTransferObject\Data;

/** A code from the authenticator app, or one of the recovery codes instead. */
class StaffTwoFactorCodeData extends Data
{
    public function __construct(
        public readonly ?string $code = null,
        public readonly ?string $recoveryCode = null,
    ) {}
}
