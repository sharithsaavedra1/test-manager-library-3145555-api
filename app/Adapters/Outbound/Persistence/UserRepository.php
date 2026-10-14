<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\UserRepositoryInterface;
use App\Domain\Model\User;

final class UserRepository extends DatabaseRepository implements UserRepositoryInterface
{
    public function findById(string $id): ?User
    {
        $row = $this->table('user')->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        return new User($row->id, $row->username, $row->password_hash, $row->role);
    }

    public function findByUsername(string $username): ?User
    {
        $normalized = strtolower(trim($username));
        $row = $this->table('user')->where('username', $normalized)->first();
        if (!$row) {
            return null;
        }

        return new User($row->id, $row->username, $row->password_hash, $row->role);
    }

    public function save(User $user): void
    {
        $this->table('user')->updateOrInsert(
            ['id' => $user->getId()],
            [
                'username' => $user->getUsername(),
                'password_hash' => $user->getPasswordHash(),
                'role' => $user->getRole(),
            ]
        );
    }
}
