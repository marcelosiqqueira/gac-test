<?php

namespace Tests\Unit;

use App\DTOs\UserRegisterDTO;
use App\Models\User;
use App\Repositories\Eloquent\UserRepository;
use App\Services\UserService;
use Mockery;
use Tests\TestCase;

beforeEach(function () {
    $this->userRepositoryMock = Mockery::mock(UserRepository::class);
    $this->userService = new UserService($this->userRepositoryMock);
});

afterEach(function () {
    Mockery::close();
});

it('creates a user successfully', function () {
    $userData = [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'securepassword',
    ];

    $userRegisterDTO = new UserRegisterDTO(
        name: $userData['name'],
        email: $userData['email'],
        password: $userData['password'],
        password_confirmation: $userData['password'],
    );

    $mockUser = new User();
    $mockUser->id = 1;
    $mockUser->name = $userData['name'];
    $mockUser->email = $userData['email'];

    $this->userRepositoryMock->shouldReceive('create')
                             ->once()
                             ->with(Mockery::type(UserRegisterDTO::class))
                             ->andReturn($mockUser);

    $createdUser = $this->userService->create($userRegisterDTO);

    expect($createdUser)->toBeInstanceOf(User::class)
        ->and($createdUser->id)->toBe(1)
        ->and($createdUser->name)->toBe('John Doe')
        ->and($createdUser->email)->toBe('john@example.com');
});
