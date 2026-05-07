<?php

declare(strict_types=1);

namespace App\Tests\Unit\Application\Order\UseCase\CreateOrderQuote;

use App\Application\Order\UseCase\CreateOrderQuote\CreateOrderQuoteCommand;
use App\Application\Order\UseCase\CreateOrderQuote\CreateOrderQuoteUseCase;
use App\Entity\Order as FlatOrder;
use App\Entity\Project as FlatProject;
use App\Infrastructure\Order\Translator\OrderDddToFlatTranslator;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use stdClass;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

#[AllowMockObjectsWithoutExpectations]
final class CreateOrderQuoteUseCaseTest extends TestCase
{
    public function testCreatePersistsAndReturnsLegacyId(): void
    {
        $useCase = $this->makeUseCase(persistedId: 55);

        $id = $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: null,
            reference: 'D202601-001',
            contractType: 'forfait',
            amount: 10000.0,
        ));

        $this->assertTrue($id->isLegacy());
        $this->assertSame(55, $id->toLegacyInt());
    }

    public function testContractTypeForfait(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatOrder $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: null,
            reference: 'D202601-002',
            contractType: 'forfait',
            amount: 5000.0,
        ));

        $this->assertNotNull($persistedFlat);
    }

    public function testContractTypeRegie(): void
    {
        $useCase = $this->makeUseCase(persistedId: 1);

        $id = $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: null,
            reference: 'D202601-003',
            contractType: 'regie',
            amount: 5000.0,
        ));

        $this->assertSame(1, $id->toLegacyInt());
    }

    public function testContractTypeAlternativeSpellings(): void
    {
        $useCase = $this->makeUseCase(persistedId: 1);

        $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: null,
            reference: 'D-EN-1',
            contractType: 'fixed_price',
            amount: 5000.0,
        ));
        $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: null,
            reference: 'D-EN-2',
            contractType: 'time_and_material',
            amount: 5000.0,
        ));

        $this->expectNotToPerformAssertions();
    }

    public function testInvalidContractTypeRejected(): void
    {
        $useCase = $this->makeUseCase(persistedId: 1);

        $this->expectException(InvalidArgumentException::class);
        $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: null,
            reference: 'D-bad',
            contractType: 'invalid',
            amount: 100.0,
        ));
    }

    public function testTitleAndDescriptionApplied(): void
    {
        $persistedFlat = null;
        $useCase = $this->makeUseCase(persistedId: 1, persistCapture: function (FlatOrder $flat) use (&$persistedFlat) {
            $persistedFlat = clone $flat;
        });

        $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: null,
            reference: 'D-TT',
            contractType: 'forfait',
            amount: 5000.0,
            title: 'Custom title',
            description: 'Detailed scope',
        ));

        $this->assertSame('Custom title', $persistedFlat->name);
        $this->assertSame('Detailed scope', $persistedFlat->description);
    }

    public function testProjectAttached(): void
    {
        $project = new FlatProject();
        (new ReflectionProperty(FlatProject::class, 'id'))->setValue($project, 33);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('find')->willReturnCallback(
            static fn (string $class, mixed $id): ?FlatProject => FlatProject::class === $class && 33 === $id ? $project : null,
        );
        $em->method('persist')->willReturnCallback(function (FlatOrder $flat): void {
            (new ReflectionProperty(FlatOrder::class, 'id'))->setValue($flat, 9);
        });
        $em->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        $useCase = new CreateOrderQuoteUseCase($em, $this->makeCompanyContext(), new OrderDddToFlatTranslator(), $bus);

        $id = $useCase->execute(new CreateOrderQuoteCommand(
            clientId: 1,
            projectId: 33,
            reference: 'D-PROJ',
            contractType: 'forfait',
            amount: 1000.0,
        ));

        $this->assertSame(9, $id->toLegacyInt());
    }

    /**
     * @param callable(FlatOrder): void|null $persistCapture
     */
    private function makeUseCase(int $persistedId, ?callable $persistCapture = null): CreateOrderQuoteUseCase
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('persist')->willReturnCallback(function (FlatOrder $flat) use ($persistedId, $persistCapture): void {
            (new ReflectionProperty(FlatOrder::class, 'id'))->setValue($flat, $persistedId);
            if ($persistCapture !== null) {
                $persistCapture($flat);
            }
        });
        $em->method('flush');

        $bus = $this->createMock(MessageBusInterface::class);
        $bus->method('dispatch')->willReturn(new Envelope(new stdClass()));

        return new CreateOrderQuoteUseCase($em, $this->makeCompanyContext(), new OrderDddToFlatTranslator(), $bus);
    }

    private function makeCompanyContext(): \App\Security\CompanyContext
    {
        $ctx = $this->createMock(\App\Security\CompanyContext::class);
        $ctx->method('getCurrentCompany')->willReturn(new \App\Entity\Company());

        return $ctx;
    }
}
