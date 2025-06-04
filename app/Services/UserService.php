<?php

namespace App\Services;

use App\DTOs\UserRegisterDTO;
use App\Models\User;
use App\Repositories\Eloquent\UserRepository;

class UserService
{
    private $userRepository;

    public function __construct(
        UserRepository $userRepository
    ) {
        $this->userRepository = $userRepository;
    }

    public function create(UserRegisterDTO $userRegisterDTO): User
    {
        return $this->userRepository->create($userRegisterDTO);
    }
}
