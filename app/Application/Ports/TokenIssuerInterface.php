<?php

namespace App\Application\Ports;

use App\Domain\Model\User;

interface TokenIssuerInterface
{
    public function issueToken(User $user, int $ttlSeconds = 28800): string;
    public function validateToken(string $token): ?array;
}
