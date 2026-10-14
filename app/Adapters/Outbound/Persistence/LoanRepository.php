<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\LoanRepositoryInterface;
use App\Domain\Model\Loan;
use App\Domain\Model\LoanItem;
use App\Domain\Model\Member;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class LoanRepository extends DatabaseRepository implements LoanRepositoryInterface
{
    public function findById(string $id): ?Loan
    {
        $row = $this->table('loan')->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        $items = $this->getItemsForLoan($id);
        $memberRow = $this->table('member')->where('id', $row->member_id)->first();
        $member = $memberRow ? new Member($memberRow->id, $memberRow->full_name, $memberRow->document_number) : null;

        return new Loan(
            $row->id,
            $row->member_id,
            $row->loaned_by_user_id,
            Carbon::parse($row->loaned_at),
            $items,
            $member
        );
    }

    public function findItemById(string $loanId, string $itemId): ?LoanItem
    {
        $row = $this->table('loan_item')
            ->where('loan_id', $loanId)
            ->where('id', $itemId)
            ->first();

        if (!$row) {
            return null;
        }

        return $this->hydrateItem($row);
    }

    public function search(
        ?string $from,
        ?string $to,
        ?string $status,
        ?string $memberId,
        int $page = 1,
        int $size = 20
    ): array {
        $q = $this->table('loan');

        if ($from !== null && trim($from) !== '') {
            $fromDate = Carbon::parse($from)->startOfDay()->format('Y-m-d H:i:sP');
            $q->where('loaned_at', '>=', $fromDate);
        }

        if ($to !== null && trim($to) !== '') {
            $toDate = Carbon::parse($to)->endOfDay()->format('Y-m-d H:i:sP');
            $q->where('loaned_at', '<=', $toDate);
        }

        if ($memberId !== null && trim($memberId) !== '') {
            $q->where('member_id', trim($memberId));
        }

        $rows = $q->orderBy('loaned_at', 'desc')->get();

        $now = Carbon::now('UTC');
        $allLoans = [];

        foreach ($rows as $r) {
            $items = $this->getItemsForLoan($r->id);
            $loan = new Loan(
                $r->id,
                $r->member_id,
                $r->loaned_by_user_id,
                Carbon::parse($r->loaned_at),
                $items
            );

            // Filtro por estado calculado si se especificó
            if ($status !== null && trim($status) !== '') {
                if ($loan->calculateStatus($now) !== trim($status)) {
                    continue;
                }
            }

            $allLoans[] = $loan;
        }

        $total = count($allLoans);
        $offset = ($page - 1) * $size;
        $sliced = array_slice($allLoans, $offset, $size);

        $items = array_map(fn(Loan $l) => $l->toArray($now), $sliced);

        return [
            'items' => $items,
            'page' => $page,
            'size' => $size,
            'total' => $total,
        ];
    }

    public function save(Loan $loan): void
    {
        $this->table('loan')->updateOrInsert(
            ['id' => $loan->getId()],
            [
                'member_id' => $loan->getMemberId(),
                'loaned_by_user_id' => $loan->getLoanedByUserId(),
                'loaned_at' => $loan->getLoanedAt()->format('Y-m-d H:i:sP'),
            ]
        );

        foreach ($loan->getItems() as $item) {
            $this->saveItem($item);
        }
    }

    public function saveItem(LoanItem $item): void
    {
        $this->table('loan_item')->updateOrInsert(
            ['id' => $item->getId()],
            [
                'loan_id' => $item->getLoanId(),
                'book_id' => $item->getBookId(),
                'book_title' => $item->getBookTitle(),
                'isbn' => $item->getIsbn(),
                'category_name' => $item->getCategoryName(),
                'due_at' => $item->getDueAt()->format('Y-m-d H:i:sP'),
                'returned_at' => $item->getReturnedAt() ? $item->getReturnedAt()->format('Y-m-d H:i:sP') : null,
            ]
        );
    }

    public function getOverdueItems(): array
    {
        $now = Carbon::now('UTC');
        $nowStr = $now->format('Y-m-d H:i:sP');

        $rows = $this->table('loan_item')
            ->join('loan', 'loan_item.loan_id', '=', 'loan.id')
            ->join('member', 'loan.member_id', '=', 'member.id')
            ->whereNull('loan_item.returned_at')
            ->where('loan_item.due_at', '<', $nowStr)
            ->orderBy('loan_item.due_at', 'asc') // CA-09.2: más días de retraso primero
            ->select([
                'loan_item.loan_id',
                'loan_item.id as item_id',
                'loan_item.book_title',
                'member.full_name as member_name',
                'loan_item.due_at'
            ])
            ->get();

        return $rows->map(function ($r) use ($now) {
            $dueDate = Carbon::parse($r->due_at);
            $daysLate = (int)$dueDate->diffInDays($now);

            return [
                'loan_id' => $r->loan_id,
                'item_id' => $r->item_id,
                'book_title' => $r->book_title,
                'member_name' => $r->member_name,
                'due_at' => $dueDate->toIso8601ZuluString(),
                'days_late' => $daysLate,
            ];
        })->all();
    }

    private function getItemsForLoan(string $loanId): array
    {
        $rows = $this->table('loan_item')->where('loan_id', $loanId)->get();
        return $rows->map(fn($r) => $this->hydrateItem($r))->all();
    }

    private function hydrateItem($row): LoanItem
    {
        return new LoanItem(
            $row->id,
            $row->loan_id,
            $row->book_id,
            $row->book_title,
            $row->isbn,
            $row->category_name,
            Carbon::parse($row->due_at),
            $row->returned_at ? Carbon::parse($row->returned_at) : null
        );
    }
}
