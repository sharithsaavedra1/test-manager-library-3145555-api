<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\ReportReaderInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class ReportReader extends DatabaseRepository implements ReportReaderInterface
{
    public function getMostBorrowedReport(string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay()->format('Y-m-d H:i:sP');
        $toDate = Carbon::parse($to)->endOfDay()->format('Y-m-d H:i:sP');

        // ADR-004: Agrupa por book_id, book_title y category_name congelados en loan_item
        $rows = $this->table('loan_item')
            ->join('loan', 'loan_item.loan_id', '=', 'loan.id')
            ->where('loan.loaned_at', '>=', $fromDate)
            ->where('loan.loaned_at', '<=', $toDate)
            ->groupBy(['loan_item.book_id', 'loan_item.book_title', 'loan_item.category_name'])
            ->select([
                'loan_item.book_id',
                'loan_item.book_title',
                'loan_item.category_name',
                DB::raw('COUNT(loan_item.id) as times_borrowed')
            ])
            ->orderBy('times_borrowed', 'desc')
            ->orderBy('loan_item.book_title', 'asc')
            ->get();

        return $rows->map(function ($r) {
            return [
                'book_id' => $r->book_id,
                'book_title' => $r->book_title,
                'category_name' => $r->category_name,
                'times_borrowed' => (int)$r->times_borrowed,
            ];
        })->all();
    }
}
