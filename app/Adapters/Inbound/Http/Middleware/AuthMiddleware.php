<?php

namespace App\Adapters\Inbound\Http\Middleware;

use App\Adapters\Inbound\Http\Response\ErrorResponse;
use App\Application\Ports\TokenIssuerInterface;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AuthMiddleware
{
    private TokenIssuerInterface $tokenIssuer;

    public function __construct(TokenIssuerInterface $tokenIssuer)
    {
        $this->tokenIssuer = $tokenIssuer;
    }

    public function handle(Request $request, Closure $next, ?string $requiredRole = null): Response
    {
        $authHeader = $request->header('Authorization', '');
        if (!preg_match('/^Bearer\s+(.+)$/i', $authHeader, $matches)) {
            return ErrorResponse::create('INVALID_CREDENTIALS', 'Token de autenticación requerido o inválido.', 401);
        }

        $token = $matches[1];
        $payload = $this->tokenIssuer->validateToken($token);

        if ($payload === null) {
            return ErrorResponse::create('INVALID_CREDENTIALS', 'Token inválido o expirado.', 401);
        }

        // Si se requiere un rol específico (ej: admin)
        if ($requiredRole !== null && ($payload['role'] ?? '') !== $requiredRole) {
            return ErrorResponse::create('FORBIDDEN_ROLE', 'No tiene permisos para realizar esta acción.', 403);
        }

        $request->attributes->set('auth_user', $payload);

        return $next($request);
    }
}
