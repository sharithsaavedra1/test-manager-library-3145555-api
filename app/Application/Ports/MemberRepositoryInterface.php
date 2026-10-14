<?php

namespace App\Application\Ports;

use App\Domain\Model\Member;

interface MemberRepositoryInterface
{
    public function findById(string $id): ?Member;
    public function findByDocumentNumber(string $documentNumber): ?Member;
    public function search(?string $query, int $page = 1, int $size = 20): array;
    public function save(Member $member): void;
    public function countUnreturnedBooks(string $memberId): int;
    public function hasOverdueLoans(string $memberId): bool;
}
