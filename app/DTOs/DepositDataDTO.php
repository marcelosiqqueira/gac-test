<?php

namespace App\DTOs;

class DepositDataDTO
{
    public function __construct(
        public float $amount,
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            amount: (float) $data['amount'],
            description: $data['description'] ?? null,
        );
    }
}
