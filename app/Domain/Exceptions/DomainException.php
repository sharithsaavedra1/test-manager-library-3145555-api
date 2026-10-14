<?php

namespace App\Domain\Exceptions;

use Exception;

class DomainException extends Exception
{
    protected string $errorCode;
    protected int $statusCode;
    protected array $details;

    public function __construct(string $message, string $errorCode = 'VALIDATION_ERROR', int $statusCode = 422, array $details = [])
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->statusCode = $statusCode;
        $this->details = $details;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getDetails(): array
    {
        return $this->details;
    }
}
