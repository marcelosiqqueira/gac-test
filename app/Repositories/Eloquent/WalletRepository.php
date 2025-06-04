<?php

namespace App\Repositories\Eloquent;


use App\Models\Wallet;
use App\Repositories\Contracts\WalletRepositoryInterface;

class WalletRepository implements WalletRepositoryInterface
{
    public function findByUserId(int $userId)
    {
        return Wallet::where('user_id', $userId)->first();
    }

    public function findByUserIdOrCreate(int $userId)
    {
        return Wallet::firstOrCreate(['user_id' => $userId]);
    }

    public function updateBalance(Wallet $wallet, float $amount)
    {
        $wallet->balance += $amount;
        $wallet->save();
    }

    public function lockForUpdate(Wallet $wallet)
    {
         return Wallet::where('id', $wallet->id)->lockForUpdate()->first();
    }
}
