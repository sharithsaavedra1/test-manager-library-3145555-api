<?php

namespace App\Domain\ValueObjects;

use App\Domain\Exceptions\BusinessException;
use Carbon\Carbon;

final class DateRange
{
    private string $from;
    private string $to;

    public function __construct(string $from, string $to)
    {
        try {
            $fromDate = Carbon::parse($from)->startOfDay();
            $toDate = Carbon::parse($to)->endOfDay();
        } catch (\Throwable $e) {
            throw BusinessException::validationError('Rango de fechas inválido.', [
                ['field' => 'dates', 'issue' => 'formato de fecha no reconocido']
            ]);
        }

        if ($toDate->lt($fromDate)) {
            throw BusinessException::invalidDateRange();
        }

        $this->from = $fromDate->format('Y-m-d');
        $this->to = $toDate->format('Y-m-d');
    }

    public function getFrom(): string
    {
        return $this->from;
    }

    public function getTo(): string
    {
        return $this->to;
    }
}
