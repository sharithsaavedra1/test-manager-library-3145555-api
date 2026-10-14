<?php

namespace App\Domain\Exceptions;

class BusinessException extends DomainException
{
    public static function invalidCredentials(): self
    {
        return new self('Credenciales inválidas.', 'INVALID_CREDENTIALS', 401);
    }

    public static function forbiddenRole(): self
    {
        return new self('No tiene permisos para realizar esta acción.', 'FORBIDDEN_ROLE', 403);
    }

    public static function notFound(string $resource = 'Recurso'): self
    {
        return new self("{$resource} no encontrado.", 'NOT_FOUND', 404);
    }

    public static function usernameTaken(): self
    {
        return new self('El nombre de usuario ya está registrado.', 'USERNAME_TAKEN', 409);
    }

    public static function isbnTaken(): self
    {
        return new self('El ISBN ya está registrado para un libro activo.', 'ISBN_TAKEN', 409);
    }

    public static function documentTaken(): self
    {
        return new self('El documento del socio ya se encuentra registrado.', 'DOCUMENT_TAKEN', 409);
    }

    public static function bookHasOpenLoans(): self
    {
        return new self('No se puede dar de baja un libro con préstamos abiertos.', 'BOOK_HAS_OPEN_LOANS', 409);
    }

    public static function bookNotAvailable(string $bookTitle = ''): self
    {
        $msg = $bookTitle ? "No quedan ejemplares disponibles de «{$bookTitle}»." : 'No quedan ejemplares disponibles para el libro solicitado.';
        return new self($msg, 'BOOK_NOT_AVAILABLE', 409);
    }

    public static function memberHasOverdue(): self
    {
        return new self('El socio tiene libros vencidos pendientes por devolver.', 'MEMBER_HAS_OVERDUE', 409);
    }

    public static function itemAlreadyReturned(): self
    {
        return new self('La línea de préstamo ya fue devuelta previamente.', 'ITEM_ALREADY_RETURNED', 409);
    }

    public static function memberLimitExceeded(int $currentCount): self
    {
        return new self("El socio ya tiene {$currentCount} libros sin devolver y no puede superar 3.", 'MEMBER_LIMIT_EXCEEDED', 422);
    }

    public static function duplicateBookInLoan(): self
    {
        return new self('No se puede repetir el mismo libro en un préstamo.', 'DUPLICATE_BOOK_IN_LOAN', 422);
    }

    public static function invalidDateRange(): self
    {
        return new self('La fecha final no puede ser anterior a la fecha inicial.', 'INVALID_DATE_RANGE', 422);
    }

    public static function validationError(string $message, array $details = []): self
    {
        return new self($message, 'VALIDATION_ERROR', 422, $details);
    }
}
