<?php

namespace App\Application\Services;

use App\Application\Ports\LoanRepositoryInterface;

final class OverdueLoansService
{
    private LoanRepositoryInterface $loanRepo;

    public function __construct(LoanRepositoryInterface $loanRepo)
    {
        $this->loanRepo = $loanRepo;
    }

    public function execute(): array
    {
        $items = $this->loanRepo->getOverdueItems();

        return [
            'items' => $items,
        ];
    }
}
