<?php

declare(strict_types=1);

namespace App\Tests\Unit\Command;

use App\Command\CreateUserCommand;
use App\Entity\Contributor;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * Comprehensive unit tests for CreateUserCommand.
 */
class CreateUserCommandTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private UserPasswordHasherInterface $passwordHasher;
    private CreateUserCommand $command;
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->entityManager   = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher  = $this->createMock(UserPasswordHasherInterface::class);
        $this->command         = new CreateUserCommand($this->entityManager, $this->passwordHasher);
        $this->commandTester   = new CommandTester($this->command);
    }

    public function testExecuteCreatesUserSuccessfully(): void
    {
        $email     = 'test@example.com';
        $password  = 'secure123';
        $firstName = 'John';
        $lastName  = 'Doe';

        // Mock password hashing
        $hashedPassword = '$2y$13$hashedpassword';
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->willReturn($hashedPassword);

        // Track persist and flush calls
        $persistedEntities = [];
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistedEntities) {
                $persistedEntities[] = $entity;
            });

        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush');

        // Execute command
        $exitCode = $this->commandTester->execute([
            'email'     => $email,
            'password'  => $password,
            'firstName' => $firstName,
            'lastName'  => $lastName,
        ]);

        // Verify command succeeded
        $this->assertEquals(Command::SUCCESS, $exitCode);

        // Verify User was created correctly
        $this->assertCount(2, $persistedEntities);
        $user = $persistedEntities[0];
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals($email, $user->getEmail());
        $this->assertEquals($firstName, $user->getFirstName());
        $this->assertEquals($lastName, $user->getLastName());
        $this->assertEquals($hashedPassword, $user->getPassword());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());

        // Verify Contributor was created correctly
        $contributor = $persistedEntities[1];
        $this->assertInstanceOf(Contributor::class, $contributor);
        $this->assertEquals($firstName, $contributor->getFirstName());
        $this->assertEquals($lastName, $contributor->getLastName());
        $this->assertEquals($email, $contributor->getEmail());
        $this->assertTrue($contributor->isActive());
        $this->assertSame($user, $contributor->getUser());
    }

    public function testExecuteOutputsSuccessMessage(): void
    {
        $email = 'output@example.com';

        // Mock password hasher
        $this->passwordHasher
            ->method('hashPassword')
            ->willReturn('hashed');

        // Mock entity manager to set contributor ID
        $contributorId = 42;
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(function ($entity) use ($contributorId) {
                if ($entity instanceof Contributor) {
                    // Simulate database assigning an ID after flush
                    $reflection = new \ReflectionClass($entity);
                    $property   = $reflection->getProperty('id');
                    $property->setValue($entity, $contributorId);
                }
            });

        // Execute command
        $this->commandTester->execute([
            'email'     => $email,
            'password'  => 'pass',
            'firstName' => 'Test',
            'lastName'  => 'User',
        ]);

        // Verify output contains email and contributor ID
        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('User created:', $output);
        $this->assertStringContainsString($email, $output);
        $this->assertStringContainsString("Contributor #$contributorId", $output);
    }

    public function testExecuteHashesPassword(): void
    {
        $plainPassword  = 'my-secret-password';
        $hashedPassword = '$2y$13$very.long.hashed.password.string';

        // Verify hashPassword is called with correct arguments
        $this->passwordHasher
            ->expects($this->once())
            ->method('hashPassword')
            ->with(
                $this->callback(function ($user) {
                    return $user instanceof User && $user->getEmail() === 'hash@test.com';
                }),
                $plainPassword
            )
            ->willReturn($hashedPassword);

        $this->commandTester->execute([
            'email'     => 'hash@test.com',
            'password'  => $plainPassword,
            'firstName' => 'Hash',
            'lastName'  => 'Test',
        ]);

        // Success indicates password was hashed
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    public function testExecutePersistsUserBeforeContributor(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $persistOrder = [];
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$persistOrder) {
                $persistOrder[] = get_class($entity);
            });

        $this->commandTester->execute([
            'email'     => 'order@test.com',
            'password'  => 'pass',
            'firstName' => 'Order',
            'lastName'  => 'Test',
        ]);

        // Verify User is persisted before Contributor
        $this->assertEquals([User::class, Contributor::class], $persistOrder);
    }

    public function testExecuteFlushesAfterUserAndAfterContributor(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $flushCount = 0;
        $this->entityManager
            ->expects($this->exactly(2))
            ->method('flush')
            ->willReturnCallback(function () use (&$flushCount) {
                ++$flushCount;
            });

        $this->commandTester->execute([
            'email'     => 'flush@test.com',
            'password'  => 'pass',
            'firstName' => 'Flush',
            'lastName'  => 'Test',
        ]);

        // Verify flush was called twice (after user, after contributor)
        $this->assertEquals(2, $flushCount);
    }

    public function testExecuteSetsDefaultRoleUser(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $capturedUser = null;
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedUser) {
                if ($entity instanceof User) {
                    $capturedUser = $entity;
                }
            });

        $this->commandTester->execute([
            'email'     => 'role@test.com',
            'password'  => 'pass',
            'firstName' => 'Role',
            'lastName'  => 'Test',
        ]);

        $this->assertNotNull($capturedUser);
        $this->assertEquals(['ROLE_USER'], $capturedUser->getRoles());
    }

    public function testExecuteLinksContributorToUser(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $capturedUser       = null;
        $capturedContributor = null;

        $this->entityManager
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedUser, &$capturedContributor) {
                if ($entity instanceof User) {
                    $capturedUser = $entity;
                }
                if ($entity instanceof Contributor) {
                    $capturedContributor = $entity;
                }
            });

        $this->commandTester->execute([
            'email'     => 'link@test.com',
            'password'  => 'pass',
            'firstName' => 'Link',
            'lastName'  => 'Test',
        ]);

        $this->assertNotNull($capturedUser);
        $this->assertNotNull($capturedContributor);
        $this->assertSame($capturedUser, $capturedContributor->getUser());
    }

    public function testExecuteSetsContributorAsActive(): void
    {
        $this->passwordHasher->method('hashPassword')->willReturn('hashed');

        $capturedContributor = null;
        $this->entityManager
            ->method('persist')
            ->willReturnCallback(function ($entity) use (&$capturedContributor) {
                if ($entity instanceof Contributor) {
                    $capturedContributor = $entity;
                }
            });

        $this->commandTester->execute([
            'email'     => 'active@test.com',
            'password'  => 'pass',
            'firstName' => 'Active',
            'lastName'  => 'Test',
        ]);

        $this->assertNotNull($capturedContributor);
        $this->assertTrue($capturedContributor->isActive());
    }

    public function testConfigureDefinesRequiredArguments(): void
    {
        $definition = $this->command->getDefinition();

        // Verify all required arguments exist
        $this->assertTrue($definition->hasArgument('email'));
        $this->assertTrue($definition->hasArgument('password'));
        $this->assertTrue($definition->hasArgument('firstName'));
        $this->assertTrue($definition->hasArgument('lastName'));

        // Verify they are all required
        $this->assertTrue($definition->getArgument('email')->isRequired());
        $this->assertTrue($definition->getArgument('password')->isRequired());
        $this->assertTrue($definition->getArgument('firstName')->isRequired());
        $this->assertTrue($definition->getArgument('lastName')->isRequired());
    }
}
