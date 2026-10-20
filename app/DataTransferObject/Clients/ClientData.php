<?php

declare(strict_types=1);

namespace App\DataTransferObject\Clients;

class ClientData
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $email,
        public readonly ?string $phone,
        public readonly ?string $address,
        public readonly ClientProfileData $profile,
        public readonly ?string $notes,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toAttributes(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            ...$this->profile->toAttributes(),
            'notes' => $this->notes,
        ];
    }
}
