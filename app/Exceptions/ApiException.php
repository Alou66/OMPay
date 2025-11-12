<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class ApiException extends Exception
{
    protected int $httpStatusCode;

    public function __construct(
        string $message = 'Erreur API',
        int $httpStatusCode = 400,
        int $code = 0,
        ?Exception $previous = null
    ) {
        $this->httpStatusCode = $httpStatusCode;
        parent::__construct($message, $code, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $this->getMessage(),
            'error_code' => $this->getCode(),
        ], $this->httpStatusCode);
    }
}