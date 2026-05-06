<?php

declare(strict_types=1);

namespace App\Application\Client\UseCase\CreateClient;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Client\ValueObject\CompanyName;
use App\Domain\Client\ValueObject\ServiceLevel;
use App\Entity\Client as FlatClient;
use App\Infrastructure\Client\Translator\ClientDddToFlatTranslator;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use const PHP_INT_MAX;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Application Layer use case — creates a new Client via the DDD aggregate
 * and persists it through the legacy table during EPIC-001 Phase 2.
 *
 * Workflow:
 *   1. Validate command (lenient; deeper validation lives in domain VOs)
 *   2. Build DDD aggregate via Client::create() — records ClientCreatedEvent
 *   3. Translate to flat entity via ClientDddToFlatTranslator
 *   4. Persist flat entity (Doctrine) — required to obtain auto-increment id
 *   5. Wrap the new id in ClientId::fromLegacyInt(id) for the response
 *   6. Dispatch domain events on the message bus
 *
 * Why not use the ACL repository's save()? Because Phase 2 ACL save()
 * requires an existing flat entity (find then update). For *creation* we
 * need to produce a new flat entity first, then translate. A future
 * Phase 4 refactor with UUID ids will simplify this to a single
 * `$repository->save($newAggregate)` call.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 * @see ADR-0005 Client BC coexistence
 */
final readonly class CreateClientUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private CompanyContext $companyContext,
        private ClientDddToFlatTranslator $dddToFlat,
        private MessageBusInterface $messageBus,
    ) {
    }

    /**
     * @return ClientId The legacy-wrapped id of the persisted Client
     */
    public function execute(CreateClientCommand $command): ClientId
    {
        $serviceLevel = $this->parseServiceLevel($command->serviceLevel);
        $name = CompanyName::fromString($command->name);

        // Build aggregate. We use a placeholder legacy:0 id which will be
        // replaced after persistence (legacy:0 never escapes this method).
        $tempId = ClientId::fromLegacyInt(PHP_INT_MAX);
        $ddd = Client::create($tempId, $name, $serviceLevel);
        if ($command->notes !== null) {
            $ddd->addNotes($command->notes);
        }

        // Translate to flat and persist
        $company = $this->companyContext->getCurrentCompany();
        $flat = new FlatClient();
        $this->dddToFlat->applyTo($ddd, $flat, $company);
        $this->entityManager->persist($flat);
        $this->entityManager->flush();

        $persistedId = ClientId::fromLegacyInt($flat->id ?? throw new InvalidArgumentException('Persisted Client has null id'));

        // Dispatch domain events. Phase 2: handlers are optional — silently
        // skip NoHandlerForMessageException (events queued for future handlers
        // configured Phase 4+). Once the event bus has dedicated handlers,
        // remove this guard.
        foreach ($ddd->pullDomainEvents() as $event) {
            try {
                $this->messageBus->dispatch($event);
            } catch (\Symfony\Component\Messenger\Exception\NoHandlerForMessageException) {
                // No handler registered for this domain event — Phase 2 acceptable.
            }
        }

        return $persistedId;
    }

    private function parseServiceLevel(string $raw): ServiceLevel
    {
        return match (strtolower($raw)) {
            'enterprise', 'vip', 'priority' => ServiceLevel::ENTERPRISE,
            'premium', 'standard' => ServiceLevel::PREMIUM,
            'low' => ServiceLevel::STANDARD,
            default => throw new InvalidArgumentException(sprintf('Unknown service level: %s', $raw)),
        };
    }
}
