<?php

namespace App\Application\Services;

use App\Application\Ports\ReportReaderInterface;
use App\Domain\ValueObjects\DateRange;

final class MostBorrowedReportService
{
    private ReportReaderInterface $reportReader;

    public function __construct(ReportReaderInterface $reportReader)
    {
        $this->reportReader = $reportReader;
    }

    public function execute(string $from, string $to): array
    {
        $range = new DateRange($from, $to);
        $rows = $this->reportReader->getMostBorrowedReport($range->getFrom(), $range->getTo());

        return [
            'from' => $range->getFrom(),
            'to' => $range->getTo(),
            'rows' => $rows,
        ];
    }
}
