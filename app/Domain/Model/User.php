<?php

namespace App\Domain\Model;

use App\Domain\Exceptions\BusinessException;

final class User
{
    public const ROLE_ADMIN = 'admin';
    public const ROLE_LIBRARIAN = 'librarian';

    private string $id;
    private string $username;
    private string $passwordHash;
    private string $role;

    public function __construct(string $id, string $username, string $passwordHash, string $role)
    {
        $this->id = $id;
        $this->setUsername($username);
        $this->passwordHash = $passwordHash;
        $this->setRole($role);
    }

    public function getId(): string { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getPasswordHash(): string { return $this->passwordHash; }
    public function getRole(): string { return $this->role; }

    public function setUsername(string $username): void
    {
        $normalized = strtolower(trim($username));
        if ($normalized === '') {
            throw BusinessException::validationError('El nombre de usuario es obligatorio.', [
                ['field' => 'username', 'issue' => 'obligatorio']
            ]);
        }
        $this->username = $normalized;
    }

    public function setRole(string $role): void
    {
        $role = trim($role);
        if (!in_array($role, [self::ROLE_ADMIN, self::ROLE_LIBRARIAN], true)) {
            throw BusinessException::validationError('Rol inválido. Debe ser admin o librarian.', [
                ['field' => 'role', 'issue' => 'debe ser admin o librarian']
            ]);
        }
        $this->role = $role;
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isLibrarian(): bool
    {
        return $this->role === self::ROLE_LIBRARIAN;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'role' => $this->role,
        ];
    }
}
