<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Wallet;
use App\Models\Transaction;
use App\Enums\TransactionType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tests\TestCase;

// Usa o trait RefreshDatabase para cada teste
uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->user->wallet->update(['balance' => 100.00]);
    $this->token = JWTAuth::fromUser($this->user);
});

// --- Testes para getBalance ---

it('gets the wallet balance for an authenticated user', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/wallet/balance');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Saldo da carteira obtido com sucesso.',
                 'data' => [
                     'balance' => '100.00' // Esperamos o saldo inicial
                 ]
             ]);
});

it('cannot get wallet balance without authentication', function () {
    $response = $this->getJson('/api/wallet/balance');

    $response->assertStatus(401)
             ->assertJson([
                 'success' => false,
                 'message' => 'Não autorizado. Por favor, faça login novamente.',
                 'errors' => [
                     'authentication' => 'Token não fornecido ou inválido.'
                 ]
             ]);
});

// --- Testes para getTransactions ---

it('gets a list of transactions for an authenticated user', function () {
    // Adiciona algumas transações de teste para o usuário principal
    $this->user->wallet->transactions()->create([
        'type' => TransactionType::DEPOSIT,
        'amount' => 50.00,
        'description' => 'Depósito inicial',
    ]);

    $relatedUser = User::factory()->create();

    $this->user->wallet->transactions()->create([
        'type' => TransactionType::TRANSFER_SENT,
        'amount' => 25.00,
        'description' => 'Transferência de saída',
        'related_wallet_id' => $relatedUser->wallet->id,
    ]);

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/wallet/transactions');

    $response->assertStatus(200)
             ->assertJsonCount(2, 'data.transactions'); // Verifica se há 2 transações no array
});

it('gets an empty list of transactions for a user with no transactions', function () {

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->getJson('/api/wallet/transactions');

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Transações obtidas com sucesso.',
                 'data' => [
                     'transactions' => [], // Esperamos um array vazio de transações
                     'pagination' => [
                         'total' => 0,
                         'per_page' => 10,
                         'current_page' => 1,
                         "last_page"=> 1,
                         "from"=> null,
                         "to"=> null
                     ]
                 ]
             ])
             ->assertJsonCount(0, 'data.transactions');
});

it('cannot get transactions without authentication', function () {
    $response = $this->getJson('/api/wallet/transactions');

    $response->assertStatus(401);
});

// // --- Testes para deposit ---

it('allows a user to deposit money into their wallet', function () {
    $depositAmount = 50.00;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/deposit', [
        'amount' => $depositAmount,
        'description' => 'Depósito de teste',
    ]);

    $response->assertStatus(200)
             ->assertJsonStructure([
                 'success',
                 'message',
                 'data' => [
                     'transaction', // Verifica se a transação está na resposta
                     'current_balance'
                 ]
             ])
             ->assertJson([
                 'success' => true,
                 'message' => 'Depósito realizado com sucesso.',
                 'data' => [
                     'current_balance' => '150.00' // 100.00 (inicial) + 50.00 (depósito)
                 ]
             ]);

    // Verifica o saldo no banco de dados
    $this->user->wallet->refresh(); // Recarrega a carteira do usuário para obter o saldo atualizado
    $this->assertEquals(150.00, (float) $this->user->wallet->balance);

    // Verifica se a transação foi registrada no banco de dados
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $this->user->wallet->id,
        'type' => TransactionType::DEPOSIT->value,
        'amount' => $depositAmount,
        'description' => 'Depósito de teste',
        'is_reversal' => false,
    ]);
});

it('does not allow deposit with invalid amount', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/deposit', [
        'amount' => -10.00, // Quantia inválida
    ]);

    $response->assertStatus(422) // Erro de validação
             ->assertJsonValidationErrors(['amount']);
});

it('does not allow deposit without authentication', function () {
    $response = $this->postJson('/api/wallet/deposit', [
        'amount' => 100.00,
    ]);

    $response->assertStatus(401); // Sem autenticação
});

// // --- Testes para transfer ---

it('allows a user to transfer money to another user', function () {
    $receiver = User::factory()->create(); // Cria um usuário recebedor
    $receiver->wallet->update(['balance' => 20.00]); // Saldo inicial do recebedor

    $transferAmount = 30.00;

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/transfer', [
        'recipient_email' => $receiver->email,
        'amount' => $transferAmount,
        'description' => 'Transferência de teste',
    ]);

    // dd($response);

    $response->assertStatus(200)
             ->assertJson([
                 'success' => true,
                 'message' => 'Transferência realizada com sucesso.',
                 'data' => [
                     'current_balance' => '70.00' // 100.00 (inicial) - 30.00 (transferência)
                 ]
             ]);

    // Verifica as transações no banco de dados
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $this->user->wallet->id,
        'related_wallet_id' => $receiver->wallet->id,
        'type' => TransactionType::TRANSFER_SENT->value,
        'amount' => $transferAmount,
        'is_reversal' => false,
    ]);
    $this->assertDatabaseHas('transactions', [
        'wallet_id' => $receiver->wallet->id,
        'related_wallet_id' => $this->user->wallet->id,
        'type' => TransactionType::TRANSFER_RECEIVED->value,
        'amount' => $transferAmount,
        'is_reversal' => false,
    ]);
});

it('does not allow transfer with insufficient balance', function () {
    $receiver = User::factory()->create();
    $transferAmount = 150.00; // Mais do que o saldo inicial do remetente (100.00)

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/transfer', [
        'recipient_email' => $receiver->email,
        'amount' => $transferAmount,
    ]);

    $response->assertStatus(400) // InsufficientBalanceException
             ->assertJson([
                 'success' => false,
                 'message' => 'Saldo insuficiente para realizar a transferência.',
                 'errors' => [
                     'balance' => 'Saldo insuficiente para realizar a transferência.'
                 ]
             ]);

    // Garante que o saldo do remetente não foi alterado
    $this->user->wallet->fresh();
    $this->assertEquals(100.00, (float) $this->user->wallet->balance);
});

it('does not allow transfer to a non-existent recipient email', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/transfer', [
        'recipient_email' => 'nonexistent@example.com',
        'amount' => 10.00,
    ]);

    $response->assertStatus(422) // `exists:users,email` validation
             ->assertJsonValidationErrors(['recipient_email']);
});

it('does not allow transfer with invalid amount', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/transfer', [
        'recipient_email' => 'any@example.com',
        'amount' => -10.00,
    ]);

    $response->assertStatus(422)
             ->assertJsonValidationErrors(['amount']);
});

it('does not allow transfer to self', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/transfer', [
        'recipient_email' => $this->user->email, // Tentando transferir para si mesmo
        'amount' => 10.00,
    ]);

    $response->assertStatus(400) // InvalidArgumentException tratada pelo global handler
             ->assertJson([
                 'success' => false,
                 'message' => 'Não é possível transferir dinheiro para si mesmo.'
             ]);
});

it('does not allow transfer without authentication', function () {
    $receiver = User::factory()->create();
    $response = $this->postJson('/api/wallet/transfer', [
        'recipient_email' => $receiver->email,
        'amount' => 10.00,
    ]);

    $response->assertStatus(401); // Sem autenticação
});



it('does not allow reversing an already reversed transaction', function () {
    $depositAmount = 50.00;
    $originalDeposit = $this->user->wallet->transactions()->create([
        'type' => TransactionType::DEPOSIT,
        'amount' => $depositAmount,
        'description' => 'Depósito original',
    ]);
    $this->user->wallet->update(['balance' => 150.00]);

    // Simula que a transação já foi estornada
    $reversalTransaction = $this->user->wallet->transactions()->create([
        'type' => TransactionType::DEPOSIT_REVERSAL,
        'amount' => $depositAmount,
        'description' => 'Estorno do depósito original',
        'is_reversal' => true,
        'original_transaction_id' => $originalDeposit->id,
    ]);
    $this->user->wallet->update(['balance' => 100.00]); // Saldo após estorno

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/reverse', [
        'transaction_id' => $originalDeposit->id, // Tenta estornar a original novamente
    ]);

    $response->assertStatus(400) // TransactionReversalException
             ->assertJson([
                 'success' => false,
                 'message' => 'Esta transação já foi estornada ou é um estorno.',
                 'errors' => ['transaction' => 'Esta transação já foi estornada ou é um estorno.']
             ]);

    // Saldo não deve ter mudado
    $this->user->wallet->fresh();
    $this->assertEquals(100.00, (float) $this->user->wallet->balance);
});

it('does not allow reversing a non-existent transaction', function () {
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/reverse', [
        'transaction_id' => 9999, // ID inexistente
    ]);

    $response->assertStatus(422) // ModelNotFoundException ou NotFoundException
             ->assertJson([
                 'success' => false,
                 'message' => 'Os dados fornecidos são inválidos.' // Mensagem do handler global
             ]);
});

it('does not allow reversing a transaction with insufficient balance for reversal', function () {
    $depositAmount = 50.00;
    $originalDeposit = $this->user->wallet->transactions()->create([
        'type' => TransactionType::DEPOSIT,
        'amount' => $depositAmount,
        'description' => 'Depósito para estorno com saldo insuficiente',
    ]);
    $this->user->wallet->update(['balance' => 150.00]); // Saldo após depósito

    // Simula que o usuário gastou o dinheiro
    $this->user->wallet->update(['balance' => 20.00]); // Saldo agora é 20, menos que o depósito a estornar (50)

    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $this->token,
    ])->postJson('/api/wallet/reverse', [
        'transaction_id' => $originalDeposit->id,
        'reason' => 'Saldo insuficiente para estornar',
    ]);

    $response->assertStatus(400) // InsufficientBalanceException
             ->assertJson([
                 'success' => false,
                 'message' => 'Saldo insuficiente na carteira para estornar o depósito original. O valor já foi gasto.',
                 'errors' => ['balance' => 'Saldo insuficiente na carteira para estornar o depósito original. O valor já foi gasto.']
             ]);

    // Saldo não deve ter mudado pelo estorno falho
    $this->user->wallet->fresh();
    $this->assertEquals(20.00, (float) $this->user->wallet->balance);
});

it('does not allow reversing without authentication', function () {
    $response = $this->postJson('/api/wallet/reverse', [
        'transaction_id' => 1,
    ]);

    $response->assertStatus(401); // Sem autenticação
});
