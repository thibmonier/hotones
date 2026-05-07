<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Client\UseCase\CreateClient;

use App\Application\Client\UseCase\CreateClient\CreateClientCommand;
use App\Application\Client\UseCase\CreateClient\CreateClientUseCase;
use App\Entity\Client as FlatClient;
use App\Entity\Company;
use App\Infrastructure\Client\Translator\ClientDddToFlatTranslator;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\NoHandlerForMessageException;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
final class CreateClientUseCaseTest extends TestCase
{
    public function testCreatePersistsAndReturnsLegacyId(): void
    {
        $useCase = $this->makeUseCase(persistedId: 42);

        $id = $useCase->execute(new CreateClientCommand(
            name: 'Acme Corp',
            serviceLevel: 'standard',
        ));

        $this->assertTrue($id->isLegacy());
        $this->assertSame(42, $id->toLegacyInt());
    }

    public function testNameValueObjectAppliedToFlat(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatClient $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateClientCommand(name: 'Acme Corp', serviceLevel: 'standard'));

        $this->assertSame('Acme Corp', $persistedFlat->name);
    }

    public function testServiceLevelMappingEnterprise(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatClient $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateClientCommand(name: 'Acme', serviceLevel: 'vip'));

        $this->assertSame('vip', $persistedFlat->serviceLevel);
    }

    public function testServiceLevelMappingPremium(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatClient $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateClientCommand(name: 'Acme', serviceLevel: 'premium'));

        $this->assertSame('standard', $persistedFlat->serviceLevel);
    }

    public function testInvalidServiceLevelRejected(): void
    {
        $useCase = $this->makeUseCase(persistedId: 1);

        $this->expectException(InvalidArgumentException::class);
        $useCase->execute(new CreateClientCommand(name: 'Acme', serviceLevel: 'invalid'));
    }

    public function testNotesPropagatedToAggregate(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatClient $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateClientCommand(name: 'Acme', serviceLevel: 'standard', notes: 'Important'));

        $this->assertSame('Important', $persistedFlat->description);
    }

    public function testNullNotesPersistedAsNull(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatClient $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateClientCommand(name: 'Acme', serviceLevel: 'standard', notes: null));

        $this->assertNull($persistedFlat->description);
    }

    public function testNoHandlerForEventIsTolerated(): void
    {
        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willThrowException(
            new NoHandlerForMessageException('no handler'),
        );

        $useCase = $this->makeUseCase(persistedId: 7, messageBus: $bus);

        // Doit pas propager l'exception
        $id = $useCase->execute(new CreateClientCommand(name: 'Acme', serviceLevel: 'standard'));
        $this->assertSame(7, $id->toLegacyInt());
    }

    /**
     * @param callable(FlatClient): void|null $persistCapture
     */
    private function makeUseCase(
        int $persistedId,
        ?callable $persistCapture = null,
        ?MessageBusInterface $messageBus = null,
    ): CreateClientUseCase {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (FlatClient $flat) use ($persistedId, $persistCapture): void {
            // Simule auto-increment Doctrine
            (new ReflectionProperty(FlatClient::class, 'id'))->setValue($flat, $persistedId);
            if ($persistCapture !== null) {
                $persistCapture($flat);
            }
        });
        $em->method('flush');

        $companyContext = $this->createMock(CompanyContext::class);
        $companyContext->method('getCurrentCompany')->willReturn(new Company());

        // Translator is final: use real instance (stateless)
        $translator = new ClientDddToFlatTranslator();

        $bus = $messageBus ?? $this->createMock(MessageBusInterface::class);
        if ($messageBus === null) {
            $bus->method('dispatch')->willReturn(new Envelope(new \stdClass()));
        }

        return new CreateClientUseCase($em, $companyContext, $translator, $bus);
    }
}
