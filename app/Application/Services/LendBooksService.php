<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepositoryInterface;
use App\Application\Ports\CategoryRepositoryInterface;
use App\Application\Ports\LoanRepositoryInterface;
use App\Application\Ports\MemberRepositoryInterface;
use App\Domain\Exceptions\BusinessException;
use App\Domain\Model\Loan;
use App\Domain\Model\LoanItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class LendBooksService
{
    private MemberRepositoryInterface $memberRepo;
    private BookRepositoryInterface $bookRepo;
    private CategoryRepositoryInterface $catRepo;
    private LoanRepositoryInterface $loanRepo;

    public function __construct(
        MemberRepositoryInterface $memberRepo,
        BookRepositoryInterface $bookRepo,
        CategoryRepositoryInterface $catRepo,
        LoanRepositoryInterface $loanRepo
    ) {
        $this->memberRepo = $memberRepo;
        $this->bookRepo = $bookRepo;
        $this->catRepo = $catRepo;
        $this->loanRepo = $loanRepo;
    }

    /**
     * @param string $memberId
     * @param string $userId
     * @param array $items array de [ ['book_id' => '...'], ... ]
     * @return array
     */
    public function execute(string $memberId, string $userId, array $items, ?string $customLoanedAt = null): array
    {
        // CA-06.1: de 1 a 3 líneas
        $count = count($items);
        if ($count < 1 || $count > 3) {
            throw BusinessException::validationError('Un préstamo debe contener entre 1 y 3 libros.', [
                ['field' => 'items', 'issue' => 'debe contener entre 1 y 3 libros']
            ]);
        }

        // CA-06.2: no se repite el mismo libro
        $bookIds = array_map(fn($item) => $item['book_id'] ?? '', $items);
        if (count($bookIds) !== count(array_unique($bookIds))) {
            throw BusinessException::duplicateBookInLoan();
        }

        // Validar existencia de socio
        $member = $this->memberRepo->findById($memberId);
        if ($member === null) {
            throw BusinessException::notFound('Socio');
        }

        // CA-06.5: Socio con algún libro vencido no puede pedir otro (409)
        if ($this->memberRepo->hasOverdueLoans($memberId)) {
            throw BusinessException::memberHasOverdue();
        }

        // CA-06.4: Un socio no puede tener más de 3 libros sin devolver en total (422)
        $currentUnreturned = $this->memberRepo->countUnreturnedBooks($memberId);
        if ($currentUnreturned + $count > 3) {
            throw BusinessException::memberLimitExceeded($currentUnreturned);
        }

        // Obtener libros
        $books = [];
        foreach ($bookIds as $bId) {
            $book = $this->bookRepo->findById($bId);
            if ($book === null || !$book->isActive()) {
                throw BusinessException::notFound("Libro {$bId}");
            }
            if ($book->getAvailableCopies() <= 0) {
                throw BusinessException::bookNotAvailable($book->getTitle());
            }
            $books[$bId] = $book;
        }

        $now = $customLoanedAt ? Carbon::parse($customLoanedAt) : Carbon::now('UTC');
        $dueAt = (clone $now)->addDays(14); // CA-06.6: loaned_at + 14 días
        $loanId = (string)Str::uuid();

        // Operación atómica transaccional (CA-06.3 y RNF-2)
        return DB::transaction(function () use ($loanId, $memberId, $userId, $now, $dueAt, $books, $bookIds) {
            $loanItems = [];

            foreach ($bookIds as $bId) {
                $book = $books[$bId];

                // Descuento atómico con condición en base de datos
                $success = $this->bookRepo->decrementCopiesAtomically($bId);
                if (!$success) {
                    throw BusinessException::bookNotAvailable($book->getTitle());
                }

                $category = $this->catRepo->findById($book->getCategoryId());
                $categoryName = $category ? $category->getName() : 'General';

                // Congela título, ISBN y nombre de categoría en ese instante (CA-06.6 & Art. VIII)
                $itemId = (string)Str::uuid();
                $loanItems[] = new LoanItem(
                    $itemId,
                    $loanId,
                    $bId,
                    $book->getTitle(),
                    (string)$book->getIsbn(),
                    $categoryName,
                    $dueAt
                );
            }

            $loan = new Loan($loanId, $memberId, $userId, $now, $loanItems);
            $this->loanRepo->save($loan);

            return $loan->toArray($now);
        });
    }
}
