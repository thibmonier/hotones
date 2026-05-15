<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CreateUserCommand;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\User;
use App\Repository\CompanyRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comprehensive unit tests for CreateUserCommand.
 */
#[AllowMockObjectsWithoutExpectations]
class CreateUserCommandTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $entityManager;
    private \PHPUnit\Framework\MockObject\MockObject $passwordHasher;
    private CreateUserCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);

        // Stub repositories (only return values, no expectation on calls).
        $userRepository = $this->createStub(UserRepository::class);
        $userRepository->method('findOneBy')->willReturn(null); // No existing user

        $companyRepository = $this->createStub(CompanyRepository::class);

        // Create a Company instance via reflection (avoids needing a fully built fixture)
        $company = new Company();
        $reflection = new ReflectionClass($company);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setValue($company, 1);
        $nameProperty = $reflection->getProperty('name');
        $nameProperty->setValue($company, 'Test Company');
        $company->setStatus(Company::STATUS_ACTIVE);

        // Repository returns the company when findOneBy is called
        $companyRepository->method('findOneBy')->willReturn($company);
        $companyRepository->method('find')->willReturn($company);

        // Configure EntityManager to return appropriate repositories.
        // Closure return type is `object` because PHPUnit returns a generated
        // stub class whose name is not stable.
        $this->entityManager
            ->method('getRepository')
            ->willReturnCallback(static fn ($entityClass): object => match ($entityClass) {
                User::class => $userRepository,
                Company::class => $companyRepository,
                default => throw new Exception('Unexpected repository requested: '.$entityClass),
            });

        $this->command = new CreateUserCommand($this->entityManager, $this->passwordHasher);
        $this->commandTester = new CommandTester($this->command);
    }

    public function testExecuteCreatesUserSuccessfully(): void
    {
        $email = 'test@example.com';
        $password = 'secure123';
        $firstName = 'John';
        $lastName = 'Doe';

        // Mock password hashing
        $hashedPassword = '$2y$13$hashedpassword';
        $this->passwordHasher->expects($this->once())->method('hashPassword')->willReturn($hashedPassword);

        // Track persist and flush calls
        $persistedEntities = [];
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$persistedEntities): void {
                $persistedEntities[] = $entity;
            });

        $this->entityManager->expects($this->exactly(2))->method('flush');

        // Execute command
        $exitCode = $this->commandTester->execute([
            'email' => $email,
            'password' => $password,
            'firstName' => $firstName,
            'lastName' => $lastName,
        ]);

        // Verify command succeeded
        static::assertEquals(Command::SUCCESS, $exitCode);

        // Verify User was created correctly
        static::assertCount(2, $persistedEntities);
        $user = $persistedEntities[0];
        static::assertInstanceOf(User::class, $user);
        static::assertEquals($email, $user->getEmail());
        static::assertEquals($firstName, $user->getFirstName());
        static::assertEquals($lastName, $user->getLastName());
        static::assertEquals($hashedPassword, $user->getPassword());
        static::assertEquals(['ROLE_INTERVENANT', 'ROLE_USER'], $user->getRoles());

        // Verify Contributor was created correctly
        $contributor = $persistedEntities[1];
        static::assertInstanceOf(Contributor::class, $contributor);
        static::assertEquals($firstName, $contributor->getFirstName());
        static::assertEquals($lastName, $contributor->getLastName());
        static::assertEquals($email, $contributor->getEmail());
        static::assertTrue($contributor->isActive());
        static::assertSame($user, $contributor->getUser());
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        $email = 'output@example.com';

        // Mock password hasher
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        // Mock entity manager to set contributor ID
        $contributorId = 42;
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(static function ($entity) use ($contributorId): void {
                if ($entity instanceof Contributor) {
                    // Simulate database assigning an ID after flush
                    $reflection = new ReflectionClass($entity);
                    $property = $reflection->getProperty('id');
                    $property->setValue($entity, $contributorId);
                }
            });

        // Execute command
        $this->commandTester->execute([
            'email' => $email,
            'password' => 'pass',
            'firstName' => 'Test',
            'lastName' => 'User',
        ]);

        // Verify output contains email and contributor ID
        $output = $this->commandTester->getDisplay();
        static::assertStringContainsString('User created:', $output);
        static::assertStringContainsString($email, $output);
        static::assertStringContainsString("Contributor ID: {$contributorId}", $output);
    }

    public function testExecuteHashesPassword(): void
    {
        $plainPassword = 'my-secret-password';
        $hashedPassword = '$2y$13$very.long.hashed.password.string';

        // Verify hashPassword is called with correct arguments
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                static::callback(static fn ($user): bool => $user instanceof User && $user->getEmail() === 'hash@test.com'),
                $plainPassword,
            )
            ->willReturn($hashedPassword);

        $this->commandTester->execute([
            'email' => 'hash@test.com',
            'password' => $plainPassword,
            'firstName' => 'Hash',
            'lastName' => 'Test',
        ]);

        // Success indicates password was hashed
        static::assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecutePersistsUserBeforeContributor(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $persistOrder = [];
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$persistOrder): void {
                $persistOrder[] = $entity::class;
            });

        $this->commandTester->execute([
            'email' => 'order@test.com',
            'password' => 'pass',
            'firstName' => 'Order',
            'lastName' => 'Test',
        ]);

        // Verify User is persisted before Contributor
        static::assertEquals([User::class, Contributor::class], $persistOrder);
    }

    public function testExecuteFlushesAfterUserAndAfterContributor(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $flushCount = 0;
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush')
            ->willReturnCallback(static function () use (&$flushCount): void {
                ++$flushCount;
            });

        $this->commandTester->execute([
            'email' => 'flush@test.com',
            'password' => 'pass',
            'firstName' => 'Flush',
            'lastName' => 'Test',
        ]);

        // Verify flush was called twice (after user, after contributor)
        static::assertSame(2, $flushCount);
    }

    public function testExecuteSetsDefaultRoleUser(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $capturedUser = null;
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$capturedUser): void {
                if ($entity instanceof User) {
                    $capturedUser = $entity;
                }
            });

        $this->commandTester->execute([
            'email' => 'role@test.com',
            'password' => 'pass',
            'firstName' => 'Role',
            'lastName' => 'Test',
        ]);

        static::assertNotNull($capturedUser);
        static::assertEquals(['ROLE_INTERVENANT', 'ROLE_USER'], $capturedUser->getRoles());
    }

    public function testExecuteLinksContributorToUser(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $capturedUser = null;
        $capturedContributor = null;

        $this->entityManager
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$capturedUser, &$capturedContributor): void {
                if ($entity instanceof User) {
                    $capturedUser = $entity;
                }
                if ($entity instanceof Contributor) {
                    $capturedContributor = $entity;
                }
            });

        $this->commandTester->execute([
            'email' => 'link@test.com',
            'password' => 'pass',
            'firstName' => 'Link',
            'lastName' => 'Test',
        ]);

        static::assertNotNull($capturedUser);
        static::assertNotNull($capturedContributor);
        static::assertSame($capturedUser, $capturedContributor->getUser());
    }

    public function testExecuteSetsContributorAsActive(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $capturedContributor = null;
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$capturedContributor): void {
                if ($entity instanceof Contributor) {
                    $capturedContributor = $entity;
                }
            });

        $this->commandTester->execute([
            'email' => 'active@test.com',
            'password' => 'pass',
            'firstName' => 'Active',
            'lastName' => 'Test',
        ]);

        static::assertNotNull($capturedContributor);
        static::assertTrue($capturedContributor->isActive());
    }

    public function testConfigureDefinesRequiredArguments(): void
    {
        $definition = $this->command->getDefinition();

        // Verify all required arguments exist
        static::assertTrue($definition->hasArgument('email'));
        static::assertTrue($definition->hasArgument('password'));
        static::assertTrue($definition->hasArgument('firstName'));
        static::assertTrue($definition->hasArgument('lastName'));

        // Verify they are all required
        static::assertTrue($definition->getArgument('email')->isRequired());
        static::assertTrue($definition->getArgument('password')->isRequired());
        static::assertTrue($definition->getArgument('firstName')->isRequired());
        static::assertTrue($definition->getArgument('lastName')->isRequired());
    }
}
