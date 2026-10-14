<?php

namespace App\Domain\Model;

use App\Domain\Exceptions\BusinessException;

final class Member
{
    private string $id;
    private string $fullName;
    private string $documentNumber;
    private int $activeLoansCount;

    public function __construct(string $id, string $fullName, string $documentNumber, int $activeLoansCount = 0)
    {
        $this->id = $id;
        $this->setFullName($fullName);
        $this->setDocumentNumber($documentNumber);
        $this->activeLoansCount = $activeLoansCount;
    }

    public function getId(): string { return $this->id; }
    public function getFullName(): string { return $this->fullName; }
    public function getDocumentNumber(): string { return $this->documentNumber; }
    public function getActiveLoansCount(): int { return $this->activeLoansCount; }

    public function setActiveLoansCount(int $count): void
    {
        $this->activeLoansCount = max(0, $count);
    }

    public function setFullName(string $name): void
    {
        $trimmed = trim($name);
        if ($trimmed === '') {
            throw BusinessException::validationError('El nombre del socio es obligatorio.', [
                ['field' => 'full_name', 'issue' => 'obligatorio']
            ]);
        }
        $this->fullName = $trimmed;
    }

    public function setDocumentNumber(string $doc): void
    {
        $trimmed = trim($doc);
        if ($trimmed === '') {
            throw BusinessException::validationError('El documento del socio es obligatorio.', [
                ['field' => 'document_number', 'issue' => 'obligatorio']
            ]);
        }
        $this->documentNumber = $trimmed;
    }

    public function toArray(bool $includeActiveLoans = false): array
    {
        $data = [
            'id' => $this->id,
            'full_name' => $this->fullName,
            'document_number' => $this->documentNumber,
        ];
        if ($includeActiveLoans) {
            $data['active_loans'] = $this->activeLoansCount;
        }
        return $data;
    }
}
