<?php

namespace App\Application\Services;

use App\Application\Ports\MemberRepositoryInterface;
use App\Domain\Exceptions\BusinessException;
use App\Domain\Model\Member;
use Illuminate\Support\Str;

final class MemberService
{
    private MemberRepositoryInterface $memberRepo;

    public function __construct(MemberRepositoryInterface $memberRepo)
    {
        $this->memberRepo = $memberRepo;
    }

    public function createMember(string $fullName, string $documentNumber): array
    {
        $trimmedDoc = trim($documentNumber);
        $trimmedName = trim($fullName);

        // CA-05.1: El documento es único: repetido 409
        $existing = $this->memberRepo->findByDocumentNumber($trimmedDoc);
        if ($existing !== null) {
            throw BusinessException::documentTaken();
        }

        $id = (string)Str::uuid();
        $member = new Member($id, $trimmedName, $trimmedDoc);
        $this->memberRepo->save($member);

        return $member->toArray();
    }

    public function searchMembers(?string $query, int $page = 1, int $size = 20): array
    {
        $size = min(max(1, $size), 50);
        $page = max(1, $page);

        return $this->memberRepo->search($query, $page, $size);
    }

    public function getMemberById(string $id): array
    {
        $member = $this->memberRepo->findById($id);
        if ($member === null) {
            throw BusinessException::notFound('Socio');
        }

        $activeLoans = $this->memberRepo->countUnreturnedBooks($id);
        $member->setActiveLoansCount($activeLoans);

        return $member->toArray(true);
    }
}
