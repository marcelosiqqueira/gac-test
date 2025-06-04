<?php

namespace App\DTOs;

use App\Http\Requests\RegisterRequest;

class UserRegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public string $password,
        public string $password_confirmation
    ) {
    }

    public static function fromRequest(RegisterRequest $request): static
    {
        return new self(
            $request['name'],
            $request['email'],
            $request['password'],
            $request['password_confirmation']
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'password' => $this->password,
        ];
    }
}
