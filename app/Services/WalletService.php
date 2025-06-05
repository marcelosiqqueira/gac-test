<?php

namespace App\Services;

use App\DTOs\DepositDataDTO;
use App\DTOs\ReverseTransactionDataDTO;
use App\DTOs\TransferDataDTO;
use App\Models\User;
use App\Models\Transaction;
use App\Enums\TransactionType;
use App\Repositories\Eloquent\TransactionRepository;
use App\Repositories\Eloquent\WalletRepository;
use Illuminate\Support\Facades\DB;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\TransactionReversalException;
use App\Exceptions\NotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use InvalidArgumentException;

class WalletService
{
    private $walletRepository;
    private $transactionRepository;

    public function __construct(
        WalletRepository $walletRepository,
        TransactionRepository $transactionRepository
    ) {
        $this->walletRepository = $walletRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function getWalletBalance(User $user): float
    {
        $wallet = $this->walletRepository->findByUserId($user->id);

        if (!$wallet) {
            throw new NotFoundException('Carteira não encontrada para o usuário.');
        }

        return $wallet->balance;
    }

    public function getUserTransactions(User $user, int $perPage = 15)
    {
        $wallet = $this->walletRepository->findByUserId($user->id);

        if (!$wallet) {
            return collect()->paginate($perPage);
        }

        return Transaction::where('wallet_id', $wallet->id)
                          ->orWhere('related_wallet_id', $wallet->id)
                          ->orderByDesc('created_at')
                          ->paginate($perPage);
    }


    public function deposit(User $user, DepositDataDTO $data): Transaction
    {

        if ($data->amount <= 0) {
            throw new \InvalidArgumentException('O valor do depósito deve ser positivo.');
        }

        return DB::transaction(function () use ($user, $data) {
            $wallet = $this->walletRepository->findByUserIdOrCreate($user->id);

            $lockedWallet = $this->walletRepository->lockForUpdate($wallet); // Trava a carteira

            $this->walletRepository->updateBalance($lockedWallet, $data->amount);

            $transaction = $this->transactionRepository->create([
                'wallet_id' => $lockedWallet->id,
                'type' => TransactionType::DEPOSIT,
                'amount' => $data->amount,
                'description' => $data->description ?? 'Depósito em conta.',
                'is_reversal' => false,
            ]);

            return $transaction;
        });
    }

    public function transfer(User $sender, TransferDataDTO $data): array
    {
        $receiver = User::where('email', $data->recipientEmail)->first();

        if ($sender->id === $receiver->id) {
            throw new InvalidArgumentException('Não é possível transferir dinheiro para si mesmo.', 400);
        }

        if ($data->amount <= 0) {
            throw new InvalidArgumentException('O valor da transferência deve ser positivo.', 400);
        }


        if (!$receiver) {
            throw new NotFoundException('Usuário destinatário não encontrado.');
        }

        return DB::transaction(function () use ($sender, $receiver, $data) {
            $senderWallet = $this->walletRepository->findByUserId($sender->id);
            $receiverWallet = $this->walletRepository->findByUserIdOrCreate($receiver->id);

            $walletsToLock = [$senderWallet, $receiverWallet];

            foreach ($walletsToLock as $wallet) {
                $this->walletRepository->lockForUpdate($wallet);
            }

            // Recarrega as instâncias após o lock, pois lockForUpdate retorna fresh()
            $senderWallet = $this->walletRepository->findByUserId($sender->id);
            $receiverWallet = $this->walletRepository->findByUserId($receiver->id);

            if ($senderWallet->balance < $data->amount) {
                throw new InsufficientBalanceException('Saldo insuficiente para realizar a transferência.');
            }

            $this->walletRepository->updateBalance($senderWallet, -$data->amount);

            $this->walletRepository->updateBalance($receiverWallet, $data->amount);

            $sentTransaction = $this->transactionRepository->create([
                'wallet_id' => $senderWallet->id,
                'related_wallet_id' => $receiverWallet->id,
                'type' => TransactionType::TRANSFER_SENT,
                'amount' => $data->amount,
                'description' => $data->description ?? "Transferência enviada para {$receiver->name}.",
                'is_reversal' => false,
            ]);

            $receivedTransaction = $this->transactionRepository->create([
                'wallet_id' => $receiverWallet->id,
                'related_wallet_id' => $senderWallet->id,
                'type' => TransactionType::TRANSFER_RECEIVED,
                'amount' => $data->amount,
                'description' => $data->description ?? "Transferência recebida de {$sender->name}.",
                'is_reversal' => false,
            ]);

            return [
                'sent' => $sentTransaction,
                'received' => $receivedTransaction,
            ];
        });
    }

    public function reverseTransaction(ReverseTransactionDataDTO $data): array
    {
        return DB::transaction(function () use ($data) {
            $originalTransaction = $this->transactionRepository->find($data->transactionId);

            if (!$originalTransaction) {
                throw new ModelNotFoundException("Transação com ID {$data->transactionId} não encontrada.");
            }

            // Verifica se a transação já foi estornada ou é um estorno
            if ($originalTransaction->is_reversal || $originalTransaction->reversalTransaction()->exists()) {
                throw new TransactionReversalException('Esta transação já foi estornada ou é um estorno.');
            }

            $originalWallet = $this->walletRepository->lockForUpdate($originalTransaction->wallet);

            $relatedWallet = null;
            if ($originalTransaction->related_wallet_id) {
                $relatedWallet = $this->walletRepository->lockForUpdate($originalTransaction->relatedWallet);
            }

            // Recarrega as instâncias após o lock
            $originalTransaction = $this->transactionRepository->find($data->transactionId);
            $originalWallet = $this->walletRepository->findByUserId($originalTransaction->wallet->user_id);

            if ($relatedWallet) {
                 $relatedWallet = $this->walletRepository->findByUserId($originalTransaction->relatedWallet->user_id);
            }


            $reversalTransactions = [];

            switch ($originalTransaction->type) {
                case TransactionType::DEPOSIT:
                    // Estorno de depósito: remover o valor da carteira
                    if ($originalWallet->balance < $originalTransaction->amount) {
                         throw new InsufficientBalanceException(
                             'Saldo insuficiente na carteira para estornar o depósito original. O valor já foi gasto.'
                         );
                    }
                    $this->walletRepository->updateBalance($originalWallet, -$originalTransaction->amount);

                    $reversalTransactions[] = $this->transactionRepository->create([
                        'wallet_id' => $originalWallet->id,
                        'type' => TransactionType::DEPOSIT_REVERSAL,
                        'amount' => $originalTransaction->amount,
                        'description' => $data->reason ?? 'Estorno de depósito: ' . $originalTransaction->description,
                        'is_reversal' => true,
                        'original_transaction_id' => $originalTransaction->id,
                    ]);
                    break;

                case TransactionType::TRANSFER_SENT:
                    if (!$relatedWallet) {
                        throw new \Exception('Carteira relacionada não encontrada para estorno de transferência enviada.');
                    }
                    // Remetente recebe de volta, Recebedor tem o valor subtraído
                    $this->walletRepository->updateBalance($originalWallet, $originalTransaction->amount); // Remetente recebe de volta
                    $this->walletRepository->updateBalance($relatedWallet, -$originalTransaction->amount);   // Recebedor tem o valor subtraído

                    $reversalTransactions[] = $this->transactionRepository->create([
                        'wallet_id' => $originalWallet->id,
                        'related_wallet_id' => $relatedWallet->id,
                        'type' => TransactionType::TRANSFER_REVERSAL,
                        'amount' => $originalTransaction->amount,
                        'description' => $data->reason ?? 'Estorno de transferência enviada: ' . $originalTransaction->description,
                        'is_reversal' => true,
                        'original_transaction_id' => $originalTransaction->id,
                    ]);

                    // Você DEVE também reverter a transação de RECEBIMENTO correspondente para consistência
                    $correspondingReceivedTransaction = $this->transactionRepository->findCorrespondingTransferReceived(
                        $relatedWallet->id, // Carteira do recebedor
                        $originalWallet->id, // Carteira do remetente
                        $originalTransaction->amount
                    );

                    if ($correspondingReceivedTransaction) {
                         $reversalTransactions[] = $this->transactionRepository->create([
                            'wallet_id' => $relatedWallet->id,
                            'related_wallet_id' => $originalWallet->id,
                            'type' => TransactionType::TRANSFER_REVERSAL,
                            'amount' => $originalTransaction->amount,
                            'description' => $data->reason ?? 'Estorno de transferência recebida (correspondente): ' . $correspondingReceivedTransaction->description,
                            'is_reversal' => true,
                            'original_transaction_id' => $correspondingReceivedTransaction->id,
                        ]);
                    }
                    break;

                case TransactionType::TRANSFER_RECEIVED:
                    if (!$relatedWallet) {
                        throw new \Exception('Carteira relacionada não encontrada para estorno de transferência recebida.');
                    }
                    if ($originalWallet->balance < $originalTransaction->amount) {
                         throw new InsufficientBalanceException(
                             'Saldo insuficiente na carteira do recebedor para estornar a transferência recebida.'
                         );
                    }
                    // Recebedor perde o valor, Remetente original recebe de volta
                    $this->walletRepository->updateBalance($originalWallet, -$originalTransaction->amount);
                    $this->walletRepository->updateBalance($relatedWallet, $originalTransaction->amount);

                    $reversalTransactions[] = $this->transactionRepository->create([
                        'wallet_id' => $originalWallet->id,
                        'related_wallet_id' => $relatedWallet->id,
                        'type' => TransactionType::TRANSFER_REVERSAL,
                        'amount' => $originalTransaction->amount,
                        'description' => $data->reason ?? 'Estorno de transferência recebida: ' . $originalTransaction->description,
                        'is_reversal' => true,
                        'original_transaction_id' => $originalTransaction->id,
                    ]);

                    // Encontrar a transação de ENVIO original e criar um estorno correspondente
                    $correspondingSentTransaction = $this->transactionRepository->findCorrespondingTransferSent(
                        $relatedWallet->id, // Carteira do remetente original
                        $originalWallet->id, // Carteira do recebedor
                        $originalTransaction->amount
                    );

                    if ($correspondingSentTransaction) {
                         $reversalTransactions[] = $this->transactionRepository->create([
                            'wallet_id' => $relatedWallet->id,
                            'related_wallet_id' => $originalWallet->id,
                            'type' => TransactionType::TRANSFER_REVERSAL,
                            'amount' => $originalTransaction->amount,
                            'description' => $data->reason ?? 'Estorno de transferência enviada (correspondente): ' . $correspondingSentTransaction->description,
                            'is_reversal' => true,
                            'original_transaction_id' => $correspondingSentTransaction->id,
                        ]);
                    }
                    break;

                default:
                    throw new TransactionReversalException('Tipo de transação não suportado para estorno.');
            }

            return $reversalTransactions;
        });
    }
}
