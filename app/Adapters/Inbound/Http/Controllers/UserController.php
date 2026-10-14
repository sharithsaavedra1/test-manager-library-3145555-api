<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use App\Application\Services\CreateLibrarianService;
use App\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class UserController
{
    private CreateLibrarianService $createLibrarianService;

    public function __construct(CreateLibrarianService $createLibrarianService)
    {
        $this->createLibrarianService = $createLibrarianService;
    }

    public function createLibrarian(Request $request): JsonResponse
    {
        $username = $request->input('username', '');
        $password = $request->input('password', '');
        $role = $request->input('role', '');

        try {
            $user = $this->createLibrarianService->execute($username, $password, $role);
            return new JsonResponse($user, 201);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }
}
