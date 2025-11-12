<?php

namespace App\Exceptions;

class InsufficientFundsException extends ApiException
{
    public function __construct(string $message = 'Solde insuffisant pour effectuer cette opération', int $code = 1001)
    {
        parent::__construct($message, 400, $code);
    }
}