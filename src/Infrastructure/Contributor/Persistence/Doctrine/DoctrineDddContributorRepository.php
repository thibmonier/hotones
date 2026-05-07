<?php

declare(strict_types=1);

namespace App\Infrastructure\Contributor\Persistence\Doctrine;

use App\Domain\Company\ValueObject\CompanyId;
use App\Domain\Contributor\Entity\Contributor as DddContributor;
use App\Domain\Contributor\Exception\ContributorNotFoundException;
use App\Domain\Contributor\Repository\ContributorRepositoryInterface;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Entity\Contributor as FlatContributor;
use App\Infrastructure\Contributor\Translator\ContributorDddToFlatTranslator;
use App\Infrastructure\Contributor\Translator\ContributorFlatToDddTranslator;
use App\Repository\ContributorRepository as FlatContributorRepository;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Anti-Corruption Layer adapter — implements DDD `ContributorRepositoryInterface`
 * by delegating to the legacy `ContributorRepository`.
 *
 * @see ADR-0008 ACL pattern
 */
final readonly class DoctrineDddContributorRepository implements ContributorRepositoryInterface
{
    public function __construct(
        private FlatContributorRepository $flatRepository,
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
        private ContributorFlatToDddTranslator $flatToDdd,
        private ContributorDddToFlatTranslator $dddToFlat,
    ) {
    }

    public function findById(ContributorId $id): DddContributor
    {
        $contributor = $this->findByIdOrNull($id);
        if ($contributor === null) {
            throw ContributorNotFoundException::withId($id);
        }

        return $contributor;
    }

    public function findByIdOrNull(ContributorId $id): ?DddContributor
    {
        if (!$id->isLegacy()) {
            return null;
        }

        $flat = $this->flatRepository->find($id->toLegacyInt());

        return $flat !== null ? $this->flatToDdd->translate($flat) : null;
    }

    /**
     * @return array<DddContributor>
     */
    public function findActive(): array
    {
        $flats = $this->flatRepository->findBy(['active' => true]);

        return array_map(
            fn (FlatContributor $flat): DddContributor => $this->flatToDdd->translate($flat),
            $flats,
        );
    }

    /**
     * @return array<DddContributor>
     */
    public function findByCompanyId(CompanyId $companyId): array
    {
        if (!$companyId->isLegacy()) {
            return [];
        }

        $flats = $this->flatRepository->findBy(['company' => $companyId->toLegacyInt()]);

        return array_map(
            fn (FlatContributor $flat): DddContributor => $this->flatToDdd->translate($flat),
            $flats,
        );
    }

    /**
     * @return array<DddContributor>
     */
    public function findByManagerId(ContributorId $managerId): array
    {
        if (!$managerId->isLegacy()) {
            return [];
        }

        $flats = $this->flatRepository->findBy(['manager' => $managerId->toLegacyInt()]);

        return array_map(
            fn (FlatContributor $flat): DddContributor => $this->flatToDdd->translate($flat),
            $flats,
        );
    }

    public function save(DddContributor $contributor): void
    {
        $id = $contributor->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Saving DDD Contributor with pure UUID id is not yet supported during Phase 2.');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt())
            ?? throw ContributorNotFoundException::withId($id);

        $company = $this->companyContext->getCurrentCompany();
        $manager = null;
        if ($contributor->getManagerId() !== null && $contributor->getManagerId()->isLegacy()) {
            $manager = $this->flatRepository->find($contributor->getManagerId()->toLegacyInt());
        }

        $this->dddToFlat->applyTo($contributor, $flat, $company, $manager);

        $this->entityManager->persist($flat);
        $this->entityManager->flush();
    }

    public function delete(DddContributor $contributor): void
    {
        $id = $contributor->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Deleting DDD Contributor with pure UUID id not yet supported');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt())
            ?? throw ContributorNotFoundException::withId($id);

        $this->entityManager->remove($flat);
        $this->entityManager->flush();
    }
}
