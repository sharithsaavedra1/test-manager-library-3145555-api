<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use App\Application\Services\BookService;
use App\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class BookController
{
    private BookService $bookService;

    public function __construct(BookService $bookService)
    {
        $this->bookService = $bookService;
    }

    public function index(Request $request): JsonResponse
    {
        $query = $request->query('q');
        $categoryId = $request->query('category_id');
        $page = (int)$request->query('page', 1);
        $size = (int)$request->query('size', 20);

        $result = $this->bookService->searchBooks($query, $categoryId, $page, $size);
        return new JsonResponse($result, 200);
    }

    public function show(string $id): JsonResponse
    {
        try {
            $book = $this->bookService->getBookById($id);
            return new JsonResponse($book, 200);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $book = $this->bookService->createBook(
                (string)$request->input('title', ''),
                (string)$request->input('author', ''),
                (string)$request->input('isbn', ''),
                (string)$request->input('category_id', ''),
                (int)$request->input('total_copies', 0)
            );
            return new JsonResponse($book, 201);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $book = $this->bookService->updateBook(
                $id,
                (string)$request->input('title', ''),
                (string)$request->input('author', ''),
                (string)$request->input('category_id', ''),
                (int)$request->input('total_copies', 0)
            );
            return new JsonResponse($book, 200);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    public function destroy(string $id): Response
    {
        try {
            $this->bookService->deleteBook($id);
            return new Response('', 204);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    public function uploadCover(Request $request, string $id): Response
    {
        try {
            $file = $request->file('file');
            if (!$file || !$file->isValid()) {
                return ErrorResponse::create('VALIDATION_ERROR', 'Archivo de portada no válido o no enviado.', 422, [
                    ['field' => 'file', 'issue' => 'archivo requerido']
                ]);
            }

            $binary = file_get_contents($file->getRealPath());
            $mime = $file->getMimeType() ?: 'image/jpeg';

            $this->bookService->uploadCover($id, $binary, $mime);
            return new Response('', 204);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }

    public function getCover(string $id): Response
    {
        try {
            $cover = $this->bookService->getCover($id);
            return new Response($cover['content'], 200, [
                'Content-Type' => $cover['mime'],
            ]);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode());
        }
    }

    public function deleteCover(string $id): Response
    {
        try {
            $this->bookService->removeCover($id);
            return new Response('', 204);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode());
        }
    }
}
