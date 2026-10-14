<?php

namespace App\Adapters\Outbound\Persistence;

use App\Application\Ports\MemberRepositoryInterface;
use App\Domain\Model\Member;
use Carbon\Carbon;

final class MemberRepository extends DatabaseRepository implements MemberRepositoryInterface
{
    public function findById(string $id): ?Member
    {
        $row = $this->table('member')->where('id', $id)->first();
        if (!$row) {
            return null;
        }

        return new Member($row->id, $row->full_name, $row->document_number);
    }

    public function findByDocumentNumber(string $documentNumber): ?Member
    {
        $row = $this->table('member')->where('document_number', $documentNumber)->first();
        if (!$row) {
            return null;
        }

        return new Member($row->id, $row->full_name, $row->document_number);
    }

    public function search(?string $query, int $page = 1, int $size = 20): array
    {
        $q = $this->table('member');

        if ($query !== null && trim($query) !== '') {
            $term = '%' . trim($query) . '%';
            $q->where(function ($sub) use ($term) {
                $sub->where('full_name', 'like', $term)
                    ->orWhere('document_number', 'like', $term);
            });
        }

        $total = $q->count();
        $offset = ($page - 1) * $size;

        $rows = $q->orderBy('full_name', 'asc')
            ->skip($offset)
            ->take($size)
            ->get();

        $items = $rows->map(function ($r) {
            $member = new Member($r->id, $r->full_name, $r->document_number);
            return $member->toArray();
        })->all();

        return [
            'items' => $items,
            'page' => $page,
            'size' => $size,
            'total' => $total,
        ];
    }

    public function save(Member $member): void
    {
        $this->table('member')->updateOrInsert(
            ['id' => $member->getId()],
            [
                'full_name' => $member->getFullName(),
                'document_number' => $member->getDocumentNumber(),
            ]
        );
    }

    public function countUnreturnedBooks(string $memberId): int
    {
        // Un socio tiene libros sin devolver si están en un loan de ese socio y loan_item.returned_at es null
        return $this->table('loan_item')
            ->join('loan', 'loan_item.loan_id', '=', 'loan.id')
            ->where('loan.member_id', $memberId)
            ->whereNull('loan_item.returned_at')
            ->count();
    }

    public function hasOverdueLoans(string $memberId): bool
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:sP');

        return $this->table('loan_item')
            ->join('loan', 'loan_item.loan_id', '=', 'loan.id')
            ->where('loan.member_id', $memberId)
            ->whereNull('loan_item.returned_at')
            ->where('loan_item.due_at', '<', $now)
            ->exists();
    }
}
