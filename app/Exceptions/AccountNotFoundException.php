<?php

namespace App\Exceptions;

class AccountNotFoundException extends ApiException
{
    public function __construct(string $message = 'Compte bancaire introuvable', int $code = 1002)
    {
        parent::__construct($message, 404, $code);
    }
}