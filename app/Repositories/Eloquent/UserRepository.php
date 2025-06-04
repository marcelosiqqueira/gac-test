<?php

namespace App\Repositories\Eloquent;

use App\DTOs\UserRegisterDTO;
use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Support\Facades\Hash;

class UserRepository implements UserRepositoryInterface
{
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function create(UserRegisterDTO $userRegisterDTO): User
    {
        $userData = $userRegisterDTO->toArray();
        $userData['password'] = Hash::make($userRegisterDTO->password);
        return $this->model->create($userData);
    }
}
