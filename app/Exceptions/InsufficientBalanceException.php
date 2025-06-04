<?php

namespace App\Exceptions;

use Exception;
use Throwable; // Importe Throwable

class InsufficientBalanceException extends Exception
{
    public function __construct(string $message = "Saldo insuficiente para a operação.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
