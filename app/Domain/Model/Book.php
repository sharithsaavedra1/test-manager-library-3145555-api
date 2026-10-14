<?php

namespace App\Domain\Model;

use App\Domain\Exceptions\BusinessException;
use App\Domain\ValueObjects\Isbn;
use App\Domain\ValueObjects\Quantity;
use Carbon\Carbon;

final class Book
{
    private string $id;
    private string $title;
    private string $author;
    private Isbn $isbn;
    private string $categoryId;
    private int $totalCopies;
    private int $availableCopies;
    private ?string $coverKey;
    private ?Carbon $deletedAt;
    private ?Category $category = null;

    public function __construct(
        string $id,
        string $title,
        string $author,
        Isbn $isbn,
        string $categoryId,
        int $totalCopies,
        ?int $availableCopies = null,
        ?string $coverKey = null,
        ?Carbon $deletedAt = null,
        ?Category $category = null
    ) {
        $this->id = $id;
        $this->setTitle($title);
        $this->setAuthor($author);
        $this->isbn = $isbn;
        $this->categoryId = $categoryId;
        
        $qty = new Quantity($totalCopies, false);
        $this->totalCopies = $qty->getValue();
        $this->availableCopies = $availableCopies ?? $this->totalCopies;

        if ($this->availableCopies < 0 || $this->availableCopies > $this->totalCopies) {
            throw BusinessException::validationError('Las copias disponibles deben estar entre 0 y el total.');
        }

        $this->coverKey = ($coverKey !== null && trim($coverKey) !== '') ? trim($coverKey) : null;
        $this->deletedAt = $deletedAt;
        $this->category = $category;
    }

    public function getId(): string { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function getAuthor(): string { return $this->author; }
    public function getIsbn(): Isbn { return $this->isbn; }
    public function getCategoryId(): string { return $this->categoryId; }
    public function getTotalCopies(): int { return $this->totalCopies; }
    public function getAvailableCopies(): int { return $this->availableCopies; }
    public function getCoverKey(): ?string { return $this->coverKey; }
    public function getDeletedAt(): ?Carbon { return $this->deletedAt; }
    public function getCategory(): ?Category { return $this->category; }

    public function setCategory(Category $cat): void { $this->category = $cat; }

    public function setTitle(string $title): void
    {
        $trimmed = trim($title);
        if ($trimmed === '') {
            throw BusinessException::validationError('El título no puede estar vacío.', [
                ['field' => 'title', 'issue' => 'obligatorio']
            ]);
        }
        $this->title = $trimmed;
    }

    public function setAuthor(string $author): void
    {
        $trimmed = trim($author);
        if ($trimmed === '') {
            throw BusinessException::validationError('El autor no puede estar vacío.', [
                ['field' => 'author', 'issue' => 'obligatorio']
            ]);
        }
        $this->author = $trimmed;
    }

    public function withdraw(): void
    {
        if ($this->availableCopies <= 0) {
            throw BusinessException::bookNotAvailable($this->title);
        }
        $this->availableCopies--;
    }

    public function restock(): void
    {
        if ($this->availableCopies >= $this->totalCopies) {
            throw BusinessException::validationError('Los ejemplares disponibles no pueden superar el total.');
        }
        $this->availableCopies++;
    }

    public function updateTotalCopies(int $newTotal): void
    {
        $qty = new Quantity($newTotal, false);
        $diff = $qty->getValue() - $this->totalCopies;
        $newAvailable = $this->availableCopies + $diff;

        if ($newAvailable < 0) {
            throw BusinessException::validationError('El ajuste de copias dejaría disponibles en número negativo.', [
                ['field' => 'total_copies', 'issue' => 'disponibles quedarían < 0']
            ]);
        }

        $this->totalCopies = $qty->getValue();
        $this->availableCopies = $newAvailable;
    }

    public function setCoverKey(?string $coverKey): void
    {
        $this->coverKey = ($coverKey !== null && trim($coverKey) !== '') ? trim($coverKey) : null;
    }

    public function softDelete(): void
    {
        $this->deletedAt = Carbon::now('UTC');
    }

    public function isActive(): bool
    {
        return $this->deletedAt === null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'author' => $this->author,
            'isbn' => (string)$this->isbn,
            'category' => $this->category ? $this->category->toArray() : ['id' => $this->categoryId, 'name' => ''],
            'total_copies' => $this->totalCopies,
            'available_copies' => $this->availableCopies,
            'cover_key' => $this->coverKey,
        ];
    }
}
