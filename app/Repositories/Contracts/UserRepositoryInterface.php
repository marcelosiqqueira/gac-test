<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\DTOs\UserRegisterDTO;

interface UserRepositoryInterface
{
    public function create(UserRegisterDTO $userRegisterDTO): User;
}
