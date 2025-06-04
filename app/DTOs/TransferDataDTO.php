<?php

namespace App\DTOs;

class TransferDataDTO
{
    public function __construct(
        public string $recipientEmail,
        public float $amount,
        public ?string $description = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            recipientEmail: $data['recipient_email'],
            amount: (float) $data['amount'],
            description: $data['description'] ?? null,
        );
    }
}
