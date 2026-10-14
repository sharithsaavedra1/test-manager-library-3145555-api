<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\BusinessException;

final class Isbn
{
    private string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (!preg_match('/^\d{13}$/', $trimmed)) {
            throw BusinessException::validationError('El ISBN debe contener exactamente 13 dígitos numéricos.', [
                ['field' => 'isbn', 'issue' => 'debe contener exactamente 13 dígitos']
            ]);
        }
        $this->value = $trimmed;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
