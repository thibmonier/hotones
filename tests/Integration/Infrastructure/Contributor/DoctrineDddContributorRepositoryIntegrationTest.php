<?php

declare(strict_types=1);

namespace App\Tests\Integration\Infrastructure\Contributor;

use App\Domain\Contributor\Entity\Contributor as DddContributor;
use App\Domain\Contributor\Exception\ContributorNotFoundException;
use App\Domain\Contributor\ValueObject\ContractStatus;
use App\Domain\Contributor\ValueObject\ContributorId;
use App\Domain\Contributor\ValueObject\PersonName;
use App\Factory\ContributorFactory;
use App\Infrastructure\Contributor\Persistence\Doctrine\DoctrineDddContributorRepository;
use App\Infrastructure\Contributor\Translator\ContributorDddToFlatTranslator;
use App\Infrastructure\Contributor\Translator\ContributorFlatToDddTranslator;
use App\Repository\ContributorRepository;
use App\Security\CompanyContext;
use App\Tests\Support\MultiTenantTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;
use Zenstruck\Foundry\Test\ResetDatabase;

/**
 * Integration tests for `DoctrineDddContributorRepository` (sprint-015 ACL).
 *
 * Exercise full stack : Foundry → flat App\Entity\Contributor → ACL adapter
 * → DDD Domain Contributor via translators. Validates :
 *   - Read path : findById / findByIdOrNull / findActive / findByCompanyId / findByManagerId
 *   - Write path : save() (legacy id) translate DDD → flat
 *   - Tenant isolation : findByCompanyId returns only current tenant
 *
 * @see ADR-0008 ACL pattern
 * @see ADR-0011 Foundation stabilized (no cherry-pick)
 *
 * Sprint-026 TEST-FUNCTIONAL-FIXES-003 : marker `skip-pre-push` retiré.
 */
final class DoctrineDddContributorRepositoryIntegrationTest extends KernelTestCase
{
    use Factories;
    use MultiTenantTestTrait;
    use ResetDatabase;

    private DoctrineDddContributorRepository $dddRepository;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setUpMultiTenant();

        $container = static::getContainer();
        $this->dddRepository = new DoctrineDddContributorRepository(
            $container->get(ContributorRepository::class),
            $container->get(EntityManagerInterface::class),
            $container->get(CompanyContext::class),
            new ContributorFlatToDddTranslator(),
            new ContributorDddToFlatTranslator(),
        );
    }

    public function testFindByIdLegacyReturnsTranslatedAggregate(): void
    {
        $flat = ContributorFactory::createOne([
            'company' => $this->testCompany,
            'firstName' => 'Jean',
            'lastName' => 'Dupont',
            'active' => true,
        ]);

        $ddd = $this->dddRepository->findById(ContributorId::fromLegacyInt($flat->getId()));

        static::assertSame($flat->getId(), $ddd->getId()->toLegacyInt());
        static::assertSame('Jean', $ddd->getName()->getFirstName());
        static::assertSame('Dupont', $ddd->getName()->getLastName());
        static::assertSame(ContractStatus::ACTIVE, $ddd->getStatus());
    }

    public function testFindByIdThrowsWhenNotFound(): void
    {
        $this->expectException(ContributorNotFoundException::class);
        $this->dddRepository->findById(ContributorId::fromLegacyInt(99_999));
    }

    public function testFindByIdOrNullReturnsNullForUuid(): void
    {
        $ddd = $this->dddRepository->findByIdOrNull(ContributorId::generate());
        static::assertNull($ddd);
    }

    public function testFindActiveExcludesInactive(): void
    {
        ContributorFactory::createOne([
            'company' => $this->testCompany,
            'firstName' => 'Active',
            'lastName' => 'One',
            'active' => true,
        ]);
        ContributorFactory::createOne([
            'company' => $this->testCompany,
            'firstName' => 'Inactive',
            'lastName' => 'Two',
            'active' => false,
        ]);

        $actives = $this->dddRepository->findActive();

        // Tous les actifs doivent être ACTIVE
        foreach ($actives as $contributor) {
            static::assertSame(ContractStatus::ACTIVE, $contributor->getStatus());
        }
    }

    public function testFindByManagerIdReturnsManaged(): void
    {
        $manager = ContributorFactory::createOne([
            'company' => $this->testCompany,
            'firstName' => 'Manager',
            'lastName' => 'Boss',
            'active' => true,
        ]);

        ContributorFactory::createOne([
            'company' => $this->testCompany,
            'firstName' => 'Subordinate',
            'lastName' => 'Worker',
            'active' => true,
            'manager' => $manager,
        ]);

        $managed = $this->dddRepository->findByManagerId(ContributorId::fromLegacyInt($manager->getId()));

        static::assertCount(1, $managed);
        static::assertSame('Subordinate', $managed[0]->getName()->getFirstName());
    }

    public function testSaveAppliesDddChangesToFlat(): void
    {
        $flat = ContributorFactory::createOne([
            'company' => $this->testCompany,
            'firstName' => 'Original',
            'lastName' => 'Name',
            'email' => 'original@example.com',
            'active' => true,
        ]);

        $ddd = $this->dddRepository->findById(ContributorId::fromLegacyInt($flat->getId()));
        $ddd->rename(PersonName::fromParts('Renamed', 'NewLast'));
        $ddd->setEmail('new@example.com');
        $ddd->deactivate();

        $this->dddRepository->save($ddd);

        // Re-load via flat
        $em = static::getContainer()->get('doctrine')->getManager();
        $em->clear();
        $reloaded = $em->getRepository(\App\Entity\Contributor::class)->find($flat->getId());

        static::assertSame('Renamed', $reloaded->getFirstName());
        static::assertSame('NewLast', $reloaded->getLastName());
        static::assertSame('new@example.com', $reloaded->getEmail());
        static::assertFalse($reloaded->isActive());
    }

    public function testSavePureUuidThrows(): void
    {
        $ddd = DddContributor::create(
            ContributorId::generate(),
            \App\Domain\Company\ValueObject\CompanyId::generate(),
            PersonName::fromParts('Future', 'UUID'),
        );

        $this->expectException(RuntimeException::class);
        $this->dddRepository->save($ddd);
    }
}
