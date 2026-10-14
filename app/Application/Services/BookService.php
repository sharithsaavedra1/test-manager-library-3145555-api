<?php

namespace App\Application\Services;

use App\Application\Ports\BookRepositoryInterface;
use App\Application\Ports\CategoryRepositoryInterface;
use App\Application\Ports\CoverStorageInterface;
use App\Domain\Exceptions\BusinessException;
use App\Domain\Model\Book;
use App\Domain\ValueObjects\Isbn;
use Illuminate\Support\Str;

final class BookService
{
    private BookRepositoryInterface $bookRepo;
    private CategoryRepositoryInterface $catRepo;
    private CoverStorageInterface $coverStorage;

    public function __construct(
        BookRepositoryInterface $bookRepo,
        CategoryRepositoryInterface $catRepo,
        CoverStorageInterface $coverStorage
    ) {
        $this->bookRepo = $bookRepo;
        $this->catRepo = $catRepo;
        $this->coverStorage = $coverStorage;
    }

    public function createBook(string $title, string $author, string $isbnStr, string $categoryId, int $totalCopies): array
    {
        $isbn = new Isbn($isbnStr);

        $category = $this->catRepo->findById($categoryId);
        if ($category === null) {
            throw BusinessException::validationError('La categoría especificada no existe.', [
                ['field' => 'category_id', 'issue' => 'categoría inexistente']
            ]);
        }

        // CA-03.2: ISBN único entre libros activos. Repetido: 409
        $existing = $this->bookRepo->findByIsbn((string)$isbn);
        if ($existing !== null && $existing->isActive()) {
            throw BusinessException::isbnTaken();
        }

        $id = (string)Str::uuid();
        $book = new Book($id, $title, $author, $isbn, $categoryId, $totalCopies);
        $book->setCategory($category);

        $this->bookRepo->save($book);

        return $book->toArray();
    }

    public function updateBook(string $id, string $title, string $author, string $categoryId, int $totalCopies): array
    {
        $book = $this->bookRepo->findById($id);
        if ($book === null || !$book->isActive()) {
            throw BusinessException::notFound('Libro');
        }

        $category = $this->catRepo->findById($categoryId);
        if ($category === null) {
            throw BusinessException::validationError('La categoría especificada no existe.', [
                ['field' => 'category_id', 'issue' => 'categoría inexistente']
            ]);
        }

        $book->setTitle($title);
        $book->setAuthor($author);
        $book->updateTotalCopies($totalCopies);
        $book->setCategory($category);

        $this->bookRepo->save($book);

        return $book->toArray();
    }

    public function deleteBook(string $id): void
    {
        $book = $this->bookRepo->findById($id);
        if ($book === null || !$book->isActive()) {
            throw BusinessException::notFound('Libro');
        }

        // CA-03.5: Un libro con préstamos abiertos no se da de baja (409)
        if ($this->bookRepo->hasOpenLoans($id)) {
            throw BusinessException::bookHasOpenLoans();
        }

        $book->softDelete();
        $this->bookRepo->save($book);
    }

    public function getBookById(string $id): array
    {
        $book = $this->bookRepo->findById($id);
        if ($book === null || !$book->isActive()) {
            throw BusinessException::notFound('Libro');
        }

        return $book->toArray();
    }

    public function searchBooks(?string $query, ?string $categoryId, int $page = 1, int $size = 20): array
    {
        $size = min(max(1, $size), 50); // CA-04.1: máx 50
        $page = max(1, $page);

        return $this->bookRepo->search($query, $categoryId, $page, $size);
    }

    public function uploadCover(string $bookId, string $binaryData, string $mimeType): void
    {
        $book = $this->bookRepo->findById($bookId);
        if ($book === null || !$book->isActive()) {
            throw BusinessException::notFound('Libro');
        }

        // Valida que sea imagen jpeg o png
        if (!in_array($mimeType, ['image/jpeg', 'image/png'], true)) {
            throw BusinessException::validationError('Solo se permiten imágenes JPEG y PNG.', [
                ['field' => 'file', 'issue' => 'tipo de imagen no permitido']
            ]);
        }

        if (strlen($binaryData) > 2 * 1024 * 1024) {
            throw BusinessException::validationError('El tamaño máximo de la portada es de 2 MB.', [
                ['field' => 'file', 'issue' => 'supera los 2 MB']
            ]);
        }

        // Si ya tenía portada previa, se elimina
        if ($book->getCoverKey()) {
            $this->coverStorage->delete($book->getCoverKey());
        }

        $coverKey = $this->coverStorage->store($binaryData, $mimeType);
        $book->setCoverKey($coverKey);
        $this->bookRepo->save($book);
    }

    public function getCover(string $bookId): array
    {
        $book = $this->bookRepo->findById($bookId);
        if ($book === null || !$book->isActive() || !$book->getCoverKey()) {
            throw BusinessException::notFound('Portada');
        }

        $data = $this->coverStorage->get($book->getCoverKey());
        if ($data === null) {
            throw BusinessException::notFound('Portada');
        }

        return $data;
    }

    public function removeCover(string $bookId): void
    {
        $book = $this->bookRepo->findById($bookId);
        if ($book === null || !$book->isActive()) {
            throw BusinessException::notFound('Libro');
        }

        $coverKey = $book->getCoverKey();
        if ($coverKey) {
            // Regla §7: primero se anula cover_key y luego se borra binario
            $book->setCoverKey(null);
            $this->bookRepo->save($book);
            $this->coverStorage->delete($coverKey);
        }
    }
}
