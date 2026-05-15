<?php

declare(strict_types=1);

namespace App\Infrastructure\Project\Persistence\Doctrine;

use App\Domain\Project\Repository\PortfolioMarginReadModelRepositoryInterface;
use App\Domain\Project\Service\PortfolioMarginRecord;
use App\Entity\Project;
use App\Security\CompanyContext;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Doctrine adapter for {@see PortfolioMarginReadModelRepositoryInterface} (US-117 T-117-02).
 *
 * Multi-tenant via {@see CompanyContext}. Filtre `status = 'active'` au niveau
 * SQL (exclut `completed` / `cancelled`). Projection minimale : `id`, `name`,
 * `coutTotalCents`, `factureTotalCents`, `margeCalculatedAt`.
 *
 * Projets sans snapshot (`margeCalculatedAt IS NULL`) sont retournés pour
 * visibilité PO : {@see PortfolioMarginCalculator} les comptabilise séparément.
 * Index `idx_project_status` réutilisé.
 */
final readonly class DoctrinePortfolioMarginReadModelRepository implements PortfolioMarginReadModelRepositoryInterface
{
    private const string STATUS_ACTIVE = 'active';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
    ) {
    }

    public function findActiveProjectsWithSnapshot(DateTimeImmutable $now): array
    {
        // $now ignoré : query time-agnostic (snapshot état courant). Utilisé par le cache decorator.
        unset($now);

        $company = $this->companyContext->getCurrentCompany();

        $rows = $this->entityManager->createQueryBuilder()
            ->select('p.id', 'p.name', 'p.coutTotalCents', 'p.factureTotalCents', 'p.margeCalculatedAt')
            ->from(Project::class, 'p')
            ->where('p.company = :company')
            ->andWhere('p.status = :status')
            ->setParameter('company', $company)
            ->setParameter('status', self::STATUS_ACTIVE)
            ->getQuery()
            ->getArrayResult();

        return array_map(
            static fn (array $row): PortfolioMarginRecord => new PortfolioMarginRecord(
                projectId: (int) $row['id'],
                projectName: (string) $row['name'],
                coutTotalCents: $row['coutTotalCents'] !== null ? (int) $row['coutTotalCents'] : null,
                factureTotalCents: $row['factureTotalCents'] !== null ? (int) $row['factureTotalCents'] : null,
                margeCalculatedAt: $row['margeCalculatedAt'] !== null ? self::toImmutable($row['margeCalculatedAt']) : null,
            ),
            $rows,
        );
    }

    private static function toImmutable(DateTimeInterface $date): DateTimeImmutable
    {
        return $date instanceof DateTimeImmutable
            ? $date
            : DateTimeImmutable::createFromInterface($date);
    }
}
