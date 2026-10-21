<?php

declare(strict_types=1);

namespace App\DataTransferObject\Clients;

use App\DataTransferObject\Data;
use App\Enums\CivilStatus;
use Carbon\CarbonImmutable;

class ClientProfileData extends Data
{
    public function __construct(
        public readonly ?CarbonImmutable $birthdate = null,
        public readonly ?CivilStatus $civilStatus = null,
        public readonly ?string $occupation = null,
    ) {}
}
