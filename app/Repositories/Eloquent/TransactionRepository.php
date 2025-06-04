<?php

namespace App\Repositories\Eloquent;

use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Repositories\Contracts\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    public function find(int $id)
    {
        return Transaction::find($id);
    }

    public function findLastByWalletAndType(int $walletId, TransactionType $type): ?Transaction
    {
        return Transaction::where('wallet_id', $walletId)
            ->where('type', $type)
            ->latest()
            ->first();
    }

    public function findCorrespondingTransferReceived(int $walletId, int $relatedWalletId, float $amount): ?Transaction
    {
        return Transaction::where('wallet_id', $walletId) // Carteira do recebedor
            ->where('related_wallet_id', $relatedWalletId) // Carteira do remetente original
            ->where('type', TransactionType::TRANSFER_RECEIVED)
            ->where('amount', $amount)
            ->whereNull('original_transaction_id') // Garante que não é um estorno
            ->orderByDesc('created_at')
            ->first();
    }

    public function findCorrespondingTransferSent(int $walletId, int $relatedWalletId, float $amount): ?Transaction
    {
         return Transaction::where('wallet_id', $walletId) // Carteira do remetente
            ->where('related_wallet_id', $relatedWalletId) // Carteira do recebedor original
            ->where('type', TransactionType::TRANSFER_SENT)
            ->where('amount', $amount)
            ->whereNull('original_transaction_id') // Garante que não é um estorno
            ->orderByDesc('created_at')
            ->first();
    }
}
