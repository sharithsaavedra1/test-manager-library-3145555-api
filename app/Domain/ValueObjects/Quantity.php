<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\BusinessException;

final class Quantity
{
    private int $value;

    public function __construct(int $value, bool $allowZero = true)
    {
        if ($allowZero && $value < 0) {
            throw BusinessException::validationError('La cantidad no puede ser negativa.', [
                ['field' => 'quantity', 'issue' => 'debe ser mayor o igual a 0']
            ]);
        }
        if (!$allowZero && $value < 1) {
            throw BusinessException::validationError('El total de ejemplares debe ser mayor o igual a 1.', [
                ['field' => 'total_copies', 'issue' => 'debe ser mayor o igual a 1']
            ]);
        }
        $this->value = $value;
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
