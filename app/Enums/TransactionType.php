<?php

namespace App\Enums;

enum TransactionType: string
{
    case DEPOSIT = 'deposit';
    case TRANSFER_SENT = 'transfer_sent';
    case TRANSFER_RECEIVED = 'transfer_received';
    case DEPOSIT_REVERSAL = 'deposit_reversal';
    case TRANSFER_REVERSAL = 'transfer_reversal';

    /**
     * Get a human-readable description for the transaction type.
     */
    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Depósito',
            self::TRANSFER_SENT => 'Transferência Enviada',
            self::TRANSFER_RECEIVED => 'Transferência Recebida',
            self::DEPOSIT_REVERSAL => 'Estorno de Depósito',
            self::TRANSFER_REVERSAL => 'Estorno de Transferência',
        };
    }

    /**
     * Determine if the transaction type is a reversal type.
     */
    public function isReversal(): bool
    {
        return match ($this) {
            self::DEPOSIT_REVERSAL, self::TRANSFER_REVERSAL => true,
            default => false,
        };
    }
}
