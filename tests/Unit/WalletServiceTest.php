<?php

namespace Tests\Unit;

use App\DTOs\DepositDataDTO;
use App\Models\User;
use App\Models\Wallet;
use App\Repositories\Eloquent\WalletRepository;
use App\Repositories\Eloquent\TransactionRepository;
use App\Services\WalletService;
use App\Exceptions\NotFoundException;
use Mockery;
use InvalidArgumentException;

beforeEach(function () {
    // Mocks dos repositórios
    $this->walletRepositoryMock = Mockery::mock(WalletRepository::class);
    $this->transactionRepositoryMock = Mockery::mock(TransactionRepository::class);

    // Instancia o WalletService, injetando os mocks dos repositórios
    $this->walletService = new WalletService(
        $this->walletRepositoryMock,
        $this->transactionRepositoryMock
    );
});

afterEach(function () {
    Mockery::close();
});

it('gets the wallet balance successfully', function () {
    $user = new User();
    $user->id = 1;

    $wallet = new Wallet(['id' => 1, 'user_id' => $user->id, 'balance' => 150.75]);

    $this->walletRepositoryMock->shouldReceive('findByUserId')
                               ->once()
                               ->with($user->id)
                               ->andReturn($wallet);

    $balance = $this->walletService->getWalletBalance($user);

    expect($balance)->toBe(150.75);
});

it('throws NotFoundException when wallet balance is requested for non-existent wallet', function () {
    $user = new User();
    $user->id = 1;

    $this->walletRepositoryMock->shouldReceive('findByUserId')
                               ->once()
                               ->with($user->id)
                               ->andReturn(null);

    $this->expectException(NotFoundException::class);
    $this->expectExceptionMessage('Carteira não encontrada para o usuário.');

    $this->walletService->getWalletBalance($user);
});

it('throws InvalidArgumentException when deposit amount is zero or negative', function () {
    $user = new User(['id' => 1]);
    $depositData = new DepositDataDTO(amount: 0.00, description: 'Invalid Deposit'); // Valor zero ou negativo

    // Não esperamos chamadas aos repositórios se a validação inicial falhar
    $this->walletRepositoryMock->shouldNotReceive('findByUserIdOrCreate');
    $this->walletRepositoryMock->shouldNotReceive('lockForUpdate');
    $this->walletRepositoryMock->shouldNotReceive('updateBalance');
    $this->transactionRepositoryMock->shouldNotReceive('create');

    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('O valor do depósito deve ser positivo.');

    // A chamada ao serviço deve estar dentro do bloco da exceção
    $this->walletService->deposit($user, $depositData);
});
