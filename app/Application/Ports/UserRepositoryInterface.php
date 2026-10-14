<?php

namespace App\Application\Ports;

use App\Domain\Model\User;

interface UserRepositoryInterface
{
    public function findById(string $id): ?User;
    public function findByUsername(string $username): ?User;
    public function save(User $user): void;
}
