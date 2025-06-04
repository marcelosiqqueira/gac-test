<?php

namespace App\Repositories\Contracts;

use App\Models\Transaction;
use App\Enums\TransactionType;

interface TransactionRepositoryInterface
{
    public function create(array $data): Transaction;
    public function find(int $id);
    public function findLastByWalletAndType(int $walletId, TransactionType $type): ?Transaction;
    public function findCorrespondingTransferReceived(int $walletId, int $relatedWalletId, float $amount): ?Transaction;
    public function findCorrespondingTransferSent(int $walletId, int $relatedWalletId, float $amount): ?Transaction;
}
