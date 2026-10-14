<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasherInterface;
use App\Application\Ports\TokenIssuerInterface;
use App\Application\Ports\UserRepositoryInterface;
use App\Domain\Exceptions\BusinessException;

final class LoginService
{
    private UserRepositoryInterface $userRepo;
    private PasswordHasherInterface $hasher;
    private TokenIssuerInterface $tokenIssuer;

    public function __construct(
        UserRepositoryInterface $userRepo,
        PasswordHasherInterface $hasher,
        TokenIssuerInterface $tokenIssuer
    ) {
        $this->userRepo = $userRepo;
        $this->hasher = $hasher;
        $this->tokenIssuer = $tokenIssuer;
    }

    public function execute(string $username, string $password): array
    {
        $normalized = strtolower(trim($username));
        $user = $this->userRepo->findByUsername($normalized);

        // CA-01.2: Con credenciales inválidas responde 401 con mensaje que no dice si falló usuario o clave
        if ($user === null || !$this->hasher->verify($password, $user->getPasswordHash())) {
            throw BusinessException::invalidCredentials();
        }

        $ttlSeconds = 28800; // 8 horas (CA-01.1)
        $token = $this->tokenIssuer->issueToken($user, $ttlSeconds);

        return [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $ttlSeconds,
            'role' => $user->getRole(),
        ];
    }
}
