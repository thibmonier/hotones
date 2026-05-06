<?php

declare(strict_types=1);

namespace App\Infrastructure\Client\Persistence\Doctrine;

use App\Domain\Client\Entity\Client as DddClient;
use App\Domain\Client\Exception\ClientNotFoundException;
use App\Domain\Client\Repository\ClientRepositoryInterface;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;
use App\Entity\Client as FlatClient;
use App\Infrastructure\Client\Translator\ClientDddToFlatTranslator;
use App\Infrastructure\Client\Translator\ClientFlatToDddTranslator;
use App\Repository\ClientRepository as FlatClientRepository;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;

/**
 * Anti-Corruption Layer adapter — implements the DDD `ClientRepositoryInterface`
 * by delegating to the legacy `ClientRepository` (Doctrine) and translating
 * results via `ClientFlatToDddTranslator` / `ClientDddToFlatTranslator`.
 *
 * Strategy (sprint-009 EPIC-001 Phase 2):
 *   - Read path: legacy repo find → translate → DDD aggregate
 *   - Write path: translate DDD → mutate or create flat entity → flush
 *   - Domain events: pulled from DDD aggregate after save and dispatched
 *     via the message bus (caller responsibility — handler injection out
 *     of scope this PR, will land DDD-PHASE2-USECASE-001)
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0005 Client BC coexistence
 */
final readonly class DoctrineDddClientRepository implements ClientRepositoryInterface
{
    public function __construct(
        private FlatClientRepository $flatRepository,
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
        private ClientFlatToDddTranslator $flatToDdd,
        private ClientDddToFlatTranslator $dddToFlat,
    ) {
    }

    public function findById(ClientId $id): DddClient
    {
        $client = $this->findByIdOrNull($id);
        if ($client === null) {
            throw new ClientNotFoundException(sprintf('Client with id %s not found', $id->getValue()));
        }

        return $client;
    }

    public function findByIdOrNull(ClientId $id): ?DddClient
    {
        if (!$id->isLegacy()) {
            // Pure UUID ids are not yet supported during Phase 2 (legacy
            // table uses int auto-increment). Phase 4 will migrate the
            // schema to UUID and remove this branch.
            return null;
        }

        $flat = $this->flatRepository->find($id->toLegacyInt());
        if ($flat === null) {
            return null;
        }

        return $this->flatToDdd->translate($flat);
    }

    public function findByEmail(Email $email): ?DddClient
    {
        // Flat Client has no top-level email — emails live on ClientContact.
        // Phase 2 ACL focuses on basic profile only. Out of scope.
        return null;
    }

    /**
     * @return array<DddClient>
     */
    public function findAll(): array
    {
        $flatClients = $this->flatRepository->findAllForCurrentCompany(['name' => 'ASC']);

        return array_map(
            fn (FlatClient $flat): DddClient => $this->flatToDdd->translate($flat),
            $flatClients,
        );
    }

    /**
     * @return array<DddClient>
     */
    public function findActive(): array
    {
        // Flat has no isActive flag — all clients are considered active.
        return $this->findAll();
    }

    public function save(DddClient $client): void
    {
        $id = $client->getId();
        $company = $this->companyContext->getCurrentCompany();

        if ($id->isLegacy()) {
            // Update existing flat entity
            $flat = $this->flatRepository->find($id->toLegacyInt())
                ?? throw new ClientNotFoundException(sprintf('Cannot update Client %s: not found', $id->getValue()));
        } else {
            // Pure DDD UUID — phase 4 path. Out of scope sprint-009.
            throw new RuntimeException('Saving DDD Client with pure UUID id is not yet supported during Phase 2. Use ClientId::fromLegacyInt() for now.');
        }

        $this->dddToFlat->applyTo($client, $flat, $company);

        $this->entityManager->persist($flat);
        $this->entityManager->flush();
    }

    public function delete(DddClient $client): void
    {
        $id = $client->getId();
        if (!$id->isLegacy()) {
            throw new RuntimeException('Deleting DDD Client with pure UUID id not yet supported');
        }

        $flat = $this->flatRepository->find($id->toLegacyInt())
            ?? throw new ClientNotFoundException(sprintf('Cannot delete Client %s: not found', $id->getValue()));

        $this->entityManager->remove($flat);
        $this->entityManager->flush();
    }
}
