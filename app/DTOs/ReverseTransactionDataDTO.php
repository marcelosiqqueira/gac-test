<?php

namespace App\DTOs;

class ReverseTransactionDataDTO
{
    public function __construct(
        public int $transactionId,
        public ?string $reason = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            transactionId: (int) $data['transaction_id'],
            reason: $data['reason'] ?? null,
        );
    }
}
