<?php

namespace App\Application\Services;

use App\Application\Ports\LoanRepositoryInterface;
use App\Domain\Exceptions\BusinessException;
use Carbon\Carbon;

final class LoanQueryService
{
    private LoanRepositoryInterface $loanRepo;

    public function __construct(LoanRepositoryInterface $loanRepo)
    {
        $this->loanRepo = $loanRepo;
    }

    public function getLoanById(string $id): array
    {
        $loan = $this->loanRepo->findById($id);
        if ($loan === null) {
            throw BusinessException::notFound('Préstamo');
        }

        return $loan->toArray();
    }

    public function listLoans(
        ?string $from,
        ?string $to,
        ?string $status,
        ?string $memberId,
        int $page = 1,
        int $size = 20
    ): array {
        $size = min(max(1, $size), 50);
        $page = max(1, $page);

        return $this->loanRepo->search($from, $to, $status, $memberId, $page, $size);
    }
}
