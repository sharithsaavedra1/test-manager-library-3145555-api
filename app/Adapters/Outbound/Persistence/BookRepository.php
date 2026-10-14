<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\BookRepositoryInterface;
use App\Domain\Model\Book;
use App\Domain\Model\Category;
use App\Domain\ValueObjects\Isbn;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class BookRepository extends DatabaseRepository implements BookRepositoryInterface
{
    public function findById(string $id): ?Book
    {
        $row = $this->table('book')->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        return $this->hydrateBook($row);
    }

    public function findByIsbn(string $isbn): ?Book
    {
        $row = $this->table('book')
            ->where('isbn', $isbn)
            ->whereNull('deleted_at')
            ->first();

        if (!$row) {
            return null;
        }

        return $this->hydrateBook($row);
    }

    public function findByIds(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = $this->table('book')->whereIn('id', $ids)->get();
        return $rows->map(fn($r) => $this->hydrateBook($r))->all();
    }

    public function search(?string $query, ?string $categoryId, int $page = 1, int $size = 20): array
    {
        $q = $this->table('book')
            ->whereNull('deleted_at');

        if ($query !== null && trim($query) !== '') {
            $term = '%' . trim($query) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('title', 'like', $term)
                    ->orWhere('author', 'like', $term);
            });
        }

        if ($categoryId !== null && trim($categoryId) !== '') {
            $q->where('category_id', trim($categoryId));
        }

        $total = $q->count();
        $offset = ($page - 1) * $size;

        $rows = $q->orderBy('title', 'asc')
            ->skip($offset)
            ->take($size)
            ->get();

        $items = $rows->map(fn($r) => $this->hydrateBook($r)->toArray())->all();

        return [
            'items' => $items,
            'page' => $page,
            'size' => $size,
            'total' => $total,
        ];
    }

    public function save(Book $book): void
    {
        $data = [
            'title' => $book->getTitle(),
            'author' => $book->getAuthor(),
            'isbn' => (string)$book->getIsbn(),
            'category_id' => $book->getCategoryId(),
            'total_copies' => $book->getTotalCopies(),
            'available_copies' => $book->getAvailableCopies(),
            'cover_key' => $book->getCoverKey(),
            'deleted_at' => $book->getDeletedAt() ? $book->getDeletedAt()->format('Y-m-d H:i:sP') : null,
        ];

        $this->table('book')->updateOrInsert(['id' => $book->getId()], $data);
    }

    public function hasOpenLoans(string $bookId): bool
    {
        // Un libro tiene préstamos abiertos si existe algún loan_item con ese book_id y returned_at es null
        return $this->table('loan_item')
            ->where('book_id', $bookId)
            ->whereNull('returned_at')
            ->exists();
    }

    public function decrementCopiesAtomically(string $bookId): bool
    {
        $driver = DB::connection()->getDriverName();
        $schema = env('DB_SCHEMA', 'library');
        $fullTable = ($driver === 'pgsql') ? "{$schema}.book" : 'book';

        $affected = DB::update(
            "UPDATE {$fullTable} SET available_copies = available_copies - 1 WHERE id = ? AND available_copies > 0",
            [$bookId]
        );

        return $affected === 1;
    }

    public function incrementCopiesAtomically(string $bookId): bool
    {
        $driver = DB::connection()->getDriverName();
        $schema = env('DB_SCHEMA', 'library');
        $fullTable = ($driver === 'pgsql') ? "{$schema}.book" : 'book';

        $affected = DB::update(
            "UPDATE {$fullTable} SET available_copies = available_copies + 1 WHERE id = ? AND available_copies < total_copies",
            [$bookId]
        );

        return $affected === 1;
    }

    private function hydrateBook($row): Book
    {
        $catRow = $this->table('category')->where('id', $row->category_id)->first();
        $category = $catRow ? new Category($catRow->id, $catRow->name) : null;

        $deletedAt = $row->deleted_at ? Carbon::parse($row->deleted_at) : null;

        return new Book(
            $row->id,
            $row->title,
            $row->author,
            new Isbn($row->isbn),
            $row->category_id,
            (int)$row->total_copies,
            (int)$row->available_copies,
            $row->cover_key,
            $deletedAt,
            $category
        );
    }
}
