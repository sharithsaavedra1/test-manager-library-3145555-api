<?php

namespace App\Application\Ports;

use App\Domain\Model\Book;

interface BookRepositoryInterface
{
    public function findById(string $id): ?Book;
    public function findByIsbn(string $isbn): ?Book;
    /** @param string[] $ids */
    public function findByIds(array $ids): array;
    public function search(?string $query, ?string $categoryId, int $page = 1, int $size = 20): array;
    public function save(Book $book): void;
    public function hasOpenLoans(string $bookId): bool;
    public function decrementCopiesAtomically(string $bookId): bool;
    public function incrementCopiesAtomically(string $bookId): bool;
}
