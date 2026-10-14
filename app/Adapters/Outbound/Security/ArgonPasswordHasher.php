<?php

namespace App\Adapters\Outbound\Security;

use App\Application\Ports\PasswordHasherInterface;

final class ArgonPasswordHasher implements PasswordHasherInterface
{
    public function hash(string $plainPassword): string
    {
        // Usa PASSWORD_ARGON2ID si está soportado, o PASSWORD_BCRYPT como fallback robusto
        $algo = defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT;
        return password_hash($plainPassword, $algo);
    }

    public function verify(string $plainPassword, string $hash): bool
    {
        return password_verify($plainPassword, $hash);
    }
}
