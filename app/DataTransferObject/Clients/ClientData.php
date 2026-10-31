<?php

declare(strict_types=1);

namespace App\DataTransferObject\Clients;

use App\DataTransferObject\Data;
use App\Enums\CivilStatus;
use App\Enums\ContactChannel;
use Carbon\CarbonImmutable;

class ClientData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email = null,
        public readonly ?string $phone = null,
        public readonly ?ContactChannel $preferredChannel = null,
        public readonly ?string $messengerUsername = null,
        public readonly ?string $telegramUsername = null,
        public readonly ?string $address = null,
        public readonly ?CarbonImmutable $birthdate = null,
        public readonly ?CivilStatus $civilStatus = null,
        public readonly ?string $occupation = null,
        public readonly ?string $notes = null,
    ) {}
}
