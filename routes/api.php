<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\WalletController;

// Rotas de autenticação (públicas)
Route::post('register', [AuthController::class, 'register'])->name('api.register');
Route::post('login', [AuthController::class, 'login'])->name('api.login');

Route::middleware('jwt.auth')->group(function () {
    Route::post('logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('refresh', [AuthController::class, 'refresh'])->name('api.refresh');
    Route::post('me', [AuthController::class, 'getUser'])->name('api.me');

    Route::prefix('wallet')->group(function () {
        Route::get('balance', [WalletController::class, 'getBalance'])->name('api.wallet.balance');
        Route::get('transactions', [WalletController::class, 'getTransactions'])->name('api.wallet.transactions');
        Route::post('deposit', [WalletController::class, 'deposit'])->name('api.wallet.deposit');
        Route::post('transfer', [WalletController::class, 'transfer'])->name('api.wallet.transfer');
        Route::post('reverse', [WalletController::class, 'reverseTransaction'])->name('api.wallet.reverse');
    });
});
