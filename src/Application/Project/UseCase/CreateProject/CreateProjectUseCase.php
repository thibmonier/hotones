<?php

declare(strict_types=1);

namespace App\Application\Project\UseCase\CreateProject;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Project\Entity\Project;
use App\Domain\Project\ValueObject\ProjectId;
use App\Domain\Project\ValueObject\ProjectType;
use App\Entity\Client as FlatClient;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Project\Translator\ProjectDddToFlatTranslator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use const PHP_INT_MAX;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Creates a new Project via the DDD aggregate during EPIC-001 Phase 2.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final readonly class CreateProjectUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectDddToFlatTranslator $dddToFlat,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function execute(CreateProjectCommand $command): ProjectId
    {
        $projectType = $this->parseProjectType($command->projectType);
        $clientId = $command->clientId !== null
            ? ClientId::fromLegacyInt($command->clientId)
            : ClientId::fromLegacyInt(PHP_INT_MAX);

        // Build aggregate (placeholder id, replaced after persist)
        $tempId = ProjectId::fromLegacyInt(PHP_INT_MAX);
        $ddd = Project::create(
            $tempId,
            $command->name,
            $clientId,
            $projectType,
            $command->isInternal,
        );
        if ($command->description !== null) {
            $ddd->updateDetails($command->name, $command->description, null);
        }

        // Resolve flat client
        $flatClient = null;
        if ($command->clientId !== null && !$command->isInternal) {
            $flatClient = $this->entityManager->find(FlatClient::class, $command->clientId);
        }

        $flat = new FlatProject();
        $this->dddToFlat->applyTo($ddd, $flat, $flatClient);
        $this->entityManager->persist($flat);
        $this->entityManager->flush();

        $persistedId = ProjectId::fromLegacyInt($flat->id ?? throw new InvalidArgumentException('Persisted Project has null id'));

        foreach ($ddd->pullDomainEvents() as $event) {
            $this->messageBus->dispatch($event);
        }

        return $persistedId;
    }

    private function parseProjectType(string $raw): ProjectType
    {
        return match (strtolower($raw)) {
            'forfait', 'fixed_price' => ProjectType::FORFAIT,
            'regie', 'time_and_material' => ProjectType::REGIE,
            default => throw new InvalidArgumentException(sprintf('Unknown project type: %s', $raw)),
        };
    }
}
