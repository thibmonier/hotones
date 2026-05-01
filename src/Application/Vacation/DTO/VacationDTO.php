<?php

declare(strict_types=1);

namespace App\Application\Vacation\DTO;

use App\Domain\Vacation\Entity\Vacation;
use DateTimeInterface;

final readonly class VacationDTO
{
    public function __construct(
        public string $id,
        public string $contributorName,
        public string $type,
        public string $typeLabel,
        public string $status,
        public string $statusLabel,
        public string $startDate,
        public string $endDate,
        public int $workingDays,
        public string $dailyHours,
        public string $totalHours,
        public ?string $reason,
        public string $createdAt,
        public ?string $approvedAt,
        public ?string $approvedByName,
    ) {
    }

    public static function fromEntity(Vacation $vacation): self
    {
        return new self(
            id: $vacation->getId()->getValue(),
            contributorName: $vacation->getContributor()->getFullName(),
            type: $vacation->getType()->value,
            typeLabel: $vacation->getTypeLabel(),
            status: $vacation->getStatus()->value,
            statusLabel: $vacation->getStatus()->label(),
            startDate: $vacation->getStartDate()->format('d/m/Y'),
            endDate: $vacation->getEndDate()->format('d/m/Y'),
            workingDays: $vacation->getNumberOfWorkingDays(),
            dailyHours: $vacation->getDailyHours()->getValue(),
            totalHours: $vacation->getTotalHours(),
            reason: $vacation->getReason(),
            createdAt: $vacation->getCreatedAt()->format(DateTimeInterface::ATOM),
            approvedAt: $vacation->getApprovedAt()?->format(DateTimeInterface::ATOM),
            approvedByName: $vacation->getApprovedBy()?->getFullName(),
        );
    }
}
