<?php

declare(strict_types=1);

namespace App\DataTransferObject\Clients;

use App\Enums\CivilStatus;
use Carbon\CarbonImmutable;

/** The details a client has that a lead doesn't, asked for when promoting. */
class ClientProfileData
{
    public function __construct(
        public readonly ?CarbonImmutable $birthdate,
        public readonly ?CivilStatus $civilStatus,
        public readonly ?string $occupation,
    ) {}

    /**
     * @return array{birthdate: CarbonImmutable|null, civil_status: CivilStatus|null, occupation: string|null}
     */
    public function toAttributes(): array
    {
        return [
            'birthdate' => $this->birthdate,
            'civil_status' => $this->civilStatus,
            'occupation' => $this->occupation,
        ];
    }
}
