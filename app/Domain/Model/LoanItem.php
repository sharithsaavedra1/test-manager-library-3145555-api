<?php

namespace App\Domain\Model;

use App\Domain\Exceptions\BusinessException;
use Carbon\Carbon;

final class LoanItem
{
    private string $id;
    private string $loanId;
    private string $bookId;
    private string $bookTitle;
    private string $isbn;
    private string $categoryName;
    private Carbon $dueAt;
    private ?Carbon $returnedAt;

    public function __construct(
        string $id,
        string $loanId,
        string $bookId,
        string $bookTitle,
        string $isbn,
        string $categoryName,
        Carbon $dueAt,
        ?Carbon $returnedAt = null
    ) {
        $this->id = $id;
        $this->loanId = $loanId;
        $this->bookId = $bookId;
        $this->bookTitle = trim($bookTitle);
        $this->isbn = trim($isbn);
        $this->categoryName = trim($categoryName);
        $this->dueAt = $dueAt;
        $this->returnedAt = $returnedAt;
    }

    public function getId(): string { return $this->id; }
    public function getLoanId(): string { return $this->loanId; }
    public function getBookId(): string { return $this->bookId; }
    public function getBookTitle(): string { return $this->bookTitle; }
    public function getIsbn(): string { return $this->isbn; }
    public function getCategoryName(): string { return $this->categoryName; }
    public function getDueAt(): Carbon { return $this->dueAt; }
    public function getReturnedAt(): ?Carbon { return $this->returnedAt; }

    public function isReturned(): bool
    {
        return $this->returnedAt !== null;
    }

    public function isOverdue(?Carbon $now = null): bool
    {
        if ($this->isReturned()) {
            return false;
        }
        $current = $now ?? Carbon::now('UTC');
        return $current->gt($this->dueAt);
    }

    public function getDaysLate(?Carbon $now = null): int
    {
        if (!$this->isOverdue($now)) {
            return 0;
        }
        $current = $now ?? Carbon::now('UTC');
        return (int)$this->dueAt->diffInDays($current);
    }

    public function markAsReturned(?Carbon $returnTime = null): void
    {
        if ($this->isReturned()) {
            throw BusinessException::itemAlreadyReturned();
        }
        $this->returnedAt = $returnTime ?? Carbon::now('UTC');
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'book_id' => $this->bookId,
            'book_title' => $this->bookTitle,
            'isbn' => $this->isbn,
            'category_name' => $this->categoryName,
            'due_at' => $this->dueAt->toIso8601ZuluString(),
            'returned_at' => $this->returnedAt ? $this->returnedAt->toIso8601ZuluString() : null,
        ];
    }
}
