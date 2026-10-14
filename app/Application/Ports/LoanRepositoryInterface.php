<?php

namespace App\Application\Ports;

use App\Domain\Model\Loan;
use App\Domain\Model\LoanItem;
use Carbon\Carbon;

interface LoanRepositoryInterface
{
    public function findById(string $id): ?Loan;
    public function findItemById(string $loanId, string $itemId): ?LoanItem;
    public function search(
        ?string $from,
        ?string $to,
        ?string $status,
        ?string $memberId,
        int $page = 1,
        int $size = 20
    ): array;
    public function save(Loan $loan): void;
    public function saveItem(LoanItem $item): void;
    /** @return array list of overdue loan items with book and member info */
    public function getOverdueItems(): array;
}
