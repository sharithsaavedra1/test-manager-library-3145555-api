<?php

namespace App\Adapters\Inbound\Http\Controllers;

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use App\Application\Services\LoginService;
use App\Domain\Exceptions\DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AuthController
{
    private LoginService $loginService;

    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
    }

    public function login(Request $request): JsonResponse
    {
        $username = $request->input('username', '');
        $password = $request->input('password', '');

        try {
            $result = $this->loginService->execute($username, $password);
            return new JsonResponse($result, 200);
        } catch (DomainException $e) {
            return ErrorResponse::create($e->getErrorCode(), $e->getMessage(), $e->getStatusCode(), $e->getDetails());
        } catch (\Throwable $e) {
            return ErrorResponse::create('SERVER_ERROR', 'Error interno del servidor.', 500);
        }
    }
}
