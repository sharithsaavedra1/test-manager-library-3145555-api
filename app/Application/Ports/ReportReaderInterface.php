<?php

namespace App\Application\Ports;

interface ReportReaderInterface
{
    /**
     * Devuelve las filas agregadas por book_id, book_title y category_name congelados
     * @return array array de [ 'book_id' => ..., 'book_title' => ..., 'category_name' => ..., 'times_borrowed' => ... ]
     */
    public function getMostBorrowedReport(string $from, string $to): array;
}
