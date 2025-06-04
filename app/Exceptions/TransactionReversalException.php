<?php

namespace App\Exceptions;

use Exception;
use Throwable; // Importe Throwable

class TransactionReversalException extends Exception
{
    public function __construct(string $message = "Não foi possível estornar a transação.", int $code = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
