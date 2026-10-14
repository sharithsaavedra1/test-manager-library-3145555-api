<?php

namespace App\Domain\Model;

use App\Domain\Exceptions\BusinessException;
use Carbon\Carbon;

final class Loan
{
    public const STATUS_OPEN = 'open';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CLOSED = 'closed';

    private string $id;
    private string $memberId;
    private string $loanedByUserId;
    private Carbon $loanedAt;
    /** @var LoanItem[] */
    private array $items = [];
    private ?Member $member = null;

    public function __construct(
        string $id,
        string $memberId,
        string $loanedByUserId,
        Carbon $loanedAt,
        array $items = [],
        ?Member $member = null
    ) {
        $this->id = $id;
        $this->memberId = $memberId;
        $this->loanedByUserId = $loanedByUserId;
        $this->loanedAt = $loanedAt;
        $this->member = $member;

        foreach ($items as $item) {
            $this->addItem($item);
        }
    }

    public function getId(): string { return $this->id; }
    public function getMemberId(): string { return $this->memberId; }
    public function getLoanedByUserId(): string { return $this->loanedByUserId; }
    public function getLoanedAt(): Carbon { return $this->loanedAt; }
    /** @return LoanItem[] */
    public function getItems(): array { return $this->items; }
    public function getMember(): ?Member { return $this->member; }

    public function setMember(Member $member): void { $this->member = $member; }

    public function addItem(LoanItem $item): void
    {
        if (count($this->items) >= 3) {
            throw BusinessException::validationError('Un préstamo no puede tener más de 3 líneas.');
        }

        foreach ($this->items as $existing) {
            if ($existing->getBookId() === $item->getBookId()) {
                throw BusinessException::duplicateBookInLoan();
            }
        }

        $this->items[] = $item;
    }

    public function calculateStatus(?Carbon $now = null): string
    {
        if (empty($this->items)) {
            return self::STATUS_CLOSED;
        }

        $allReturned = true;
        $hasOverdue = false;
        $current = $now ?? Carbon::now('UTC');

        foreach ($this->items as $item) {
            if (!$item->isReturned()) {
                $allReturned = false;
                if ($item->isOverdue($current)) {
                    $hasOverdue = true;
                }
            }
        }

        if ($allReturned) {
            return self::STATUS_CLOSED;
        }

        if ($hasOverdue) {
            return self::STATUS_OVERDUE;
        }

        return self::STATUS_OPEN;
    }

    public function toArray(?Carbon $now = null): array
    {
        return [
            'id' => $this->id,
            'member_id' => $this->memberId,
            'loaned_at' => $this->loanedAt->toIso8601ZuluString(),
            'status' => $this->calculateStatus($now),
            'items' => array_map(fn(LoanItem $i) => $i->toArray(), $this->items),
        ];
    }
}
