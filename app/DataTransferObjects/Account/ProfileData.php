<?php

declare(strict_types=1);

namespace App\DataTransferObjects\Account;

class ProfileData
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly ?string $phone,
        public readonly string $timezone,
    ) {}

    /**
     * @return array{name: string, email: string, phone: string|null, timezone: string}
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'timezone' => $this->timezone,
        ];
    }
}
