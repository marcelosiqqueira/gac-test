<?php

namespace App\Http\Controllers;

use App\DTOs\DepositDataDTO;
use App\DTOs\ReverseTransactionDataDTO;
use App\DTOs\TransferDataDTO;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\TransferRequest;
use App\Http\Requests\ReverseTransactionRequest;
use App\Services\WalletService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    private $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function getBalance(): JsonResponse
    {
        $user = auth()->user();

        $balance = $this->walletService->getWalletBalance($user);

        return ApiResponse::success(['balance' => number_format($balance, 2, '.', '')], 200, 'Saldo da carteira obtido com sucesso.');
    }

    /**
     * Obtém as transações do usuário autenticado.
     */
    public function getTransactions(Request $request): JsonResponse
    {
        $user = auth()->user();
        $perPage = $request->get('per_page', 15);

        $transactions = $this->walletService->getUserTransactions($user, (int) $perPage);

        return ApiResponse::success([
            'transactions' => $transactions->items(),
            'pagination' => [
                'total' => $transactions->total(),
                'per_page' => $transactions->perPage(),
                'current_page' => $transactions->currentPage(),
                'last_page' => $transactions->lastPage(),
                'from' => $transactions->firstItem(),
                'to' => $transactions->lastItem(),
            ]
        ], 200, 'Transações obtidas com sucesso.');
    }

    /**
     * Realiza um depósito na carteira do usuário autenticado.
     */
    public function deposit(DepositRequest $request): JsonResponse
    {
        $user = auth()->user();

        $depositData = DepositDataDTO::fromArray($request->validated());

        $transaction = $this->walletService->deposit($user, $depositData);

        return ApiResponse::success([
            'transaction' => $transaction,
            'current_balance' => number_format($user->wallet->fresh()->balance, 2, '.', '')
        ], 200, 'Depósito realizado com sucesso.');
    }

    /**
     * Realiza uma transferência de dinheiro para outro usuário.
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        $sender = auth()->user();
        $transferData = TransferDataDTO::fromArray($request->validated());

        $this->walletService->transfer($sender, $transferData);
        return ApiResponse::success([
            'current_balance' => number_format($sender->wallet->fresh()->balance, 2, '.', '')
        ], 200, 'Transferência realizada com sucesso.');
    }

    /**
     * Estorna uma transação.
     */
    public function reverseTransaction(ReverseTransactionRequest $request): JsonResponse
    {
        $reverseData = ReverseTransactionDataDTO::fromArray($request->validated());
        $this->walletService->reverseTransaction($reverseData);
        return ApiResponse::success([], 200, 'Transação estornada com sucesso.');
    }
}
