<?php

namespace App\Application\Services;

use App\Application\Ports\PasswordHasherInterface;
use App\Application\Ports\UserRepositoryInterface;
use App\Domain\Exceptions\BusinessException;
use App\Domain\Model\User;
use Illuminate\Support\Str;

final class CreateLibrarianService
{
    private UserRepositoryInterface $userRepo;
    private PasswordHasherInterface $hasher;

    public function __construct(
        UserRepositoryInterface $userRepo,
        PasswordHasherInterface $hasher
    ) {
        $this->userRepo = $userRepo;
        $this->hasher = $hasher;
    }

    public function execute(string $username, string $password, string $role): array
    {
        $normalizedUsername = strtolower(trim($username));

        // CA-02.3: Pedir el rol admin por la API responde 422
        if ($role === 'admin' || $role !== 'librarian') {
            throw BusinessException::validationError('No está permitido crear usuarios con rol admin por la API.', [
                ['field' => 'role', 'issue' => 'solo se permite crear el rol librarian']
            ]);
        }

        if (trim($password) === '') {
            throw BusinessException::validationError('La contraseña no puede estar vacía.', [
                ['field' => 'password', 'issue' => 'obligatorio']
            ]);
        }

        // CA-02.2: El nombre de usuario es único. Repetido: 409
        $existing = $this->userRepo->findByUsername($normalizedUsername);
        if ($existing !== null) {
            throw BusinessException::usernameTaken();
        }

        $id = (string)Str::uuid();
        $hash = $this->hasher->hash($password);
        $user = new User($id, $normalizedUsername, $hash, User::ROLE_LIBRARIAN);

        $this->userRepo->save($user);

        // 201 sin password ni password_hash
        return $user->toArray();
    }
}
