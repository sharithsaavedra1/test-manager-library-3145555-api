<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepositoryInterface;
use App\Application\Ports\LoanRepositoryInterface;
use App\Domain\Exceptions\BusinessException;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class ReturnItemService
{
    private LoanRepositoryInterface $loanRepo;
    private BookRepositoryInterface $bookRepo;

    public function __construct(
        LoanRepositoryInterface $loanRepo,
        BookRepositoryInterface $bookRepo
    ) {
        $this->loanRepo = $loanRepo;
        $this->bookRepo = $bookRepo;
    }

    public function execute(string $loanId, string $itemId): array
    {
        $item = $this->loanRepo->findItemById($loanId, $itemId);
        if ($item === null) {
            throw BusinessException::notFound('Línea de préstamo');
        }

        // CA-07.2: Devolver una línea ya devuelta: 409. Nunca suma dos veces.
        if ($item->isReturned()) {
            throw BusinessException::itemAlreadyReturned();
        }

        $now = Carbon::now('UTC');

        return DB::transaction(function () use ($item, $now) {
            $item->markAsReturned($now);
            $this->loanRepo->saveItem($item);

            // Suma 1 a los disponibles (CA-07.1)
            $this->bookRepo->incrementCopiesAtomically($item->getBookId());

            return $item->toArray();
        });
    }
}
