<?php

declare(strict_types=1);

namespace App\Application\Order\UseCase\CreateOrderQuote;

use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Order\Entity\Order;
use App\Domain\Order\ValueObject\ContractType;
use App\Domain\Order\ValueObject\OrderId;
use App\Domain\Shared\ValueObject\Money;
use App\Entity\Order as FlatOrder;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Order\Translator\OrderDddToFlatTranslator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;

use const PHP_INT_MAX;

use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Creates a new Order quote (status DRAFT) via DDD aggregate.
 *
 * @see ADR-0008 Anti-Corruption Layer pattern
 */
final readonly class CreateOrderQuoteUseCase
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private OrderDddToFlatTranslator $dddToFlat,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function execute(CreateOrderQuoteCommand $command): OrderId
    {
        $contractType = $this->parseContractType($command->contractType);
        $clientId = ClientId::fromLegacyInt($command->clientId);
        $amount = Money::fromAmount($command->amount);

        $tempId = OrderId::fromLegacyInt(PHP_INT_MAX);
        $ddd = Order::create(
            $tempId,
            $command->reference,
            $clientId,
            $contractType,
            $amount,
        );
        if ($command->title !== null || $command->description !== null) {
            $ddd->updateDetails($command->title, $command->description, null);
        }

        $flat = new FlatOrder();
        // Resolve flat project (Order is attached to Project, not Client directly)
        if ($command->projectId !== null) {
            $flat->project = $this->entityManager->find(FlatProject::class, $command->projectId);
        }

        $this->dddToFlat->applyTo($ddd, $flat);

        $this->entityManager->persist($flat);
        $this->entityManager->flush();

        $persistedId = OrderId::fromLegacyInt($flat->id ?? throw new InvalidArgumentException('Persisted Order has null id'));

        foreach ($ddd->pullDomainEvents() as $event) {
            $this->messageBus->dispatch($event);
        }

        return $persistedId;
    }

    private function parseContractType(string $raw): ContractType
    {
        return match (strtolower($raw)) {
            'forfait', 'fixed_price' => ContractType::FIXED_PRICE,
            'regie', 'time_and_material' => ContractType::TIME_AND_MATERIAL,
            default => throw new InvalidArgumentException(sprintf('Unknown contract type: %s', $raw)),
        };
    }
}
