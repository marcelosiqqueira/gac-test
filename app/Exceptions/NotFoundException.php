<?php

namespace App\Exceptions;

use Exception;
use Throwable;

class NotFoundException extends Exception
{
    public function __construct(string $message = "Recurso não encontrado.", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
