<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Wallet;

interface WalletRepositoryInterface
{
    public function findByUserId(int $userId);
    public function findByUserIdOrCreate(int $userId);
    public function updateBalance(Wallet $wallet, float $amount);
    public function lockForUpdate(Wallet $wallet);
}
