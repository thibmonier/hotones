<?php

declare(strict_types=1);

namespace App\Tests\Unit\Monolog\Processor;

use App\EventSubscriber\RequestIdSubscriber;
use App\Monolog\Processor\ContextProcessor;
use DateTimeImmutable;
use Monolog\Level;
use Monolog\LogRecord;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * US-095 (sprint-017 EPIC-002) — coverage Unit du processor injectant le
 * contexte HTTP / user / env dans tous les logs JSON.
 */
#[AllowMockObjectsWithoutExpectations]
final class ContextProcessorTest extends TestCase
{
    public function testHttpContextEnrichmentWithoutUser(): void
    {
        $request = Request::create('/api/clients', 'POST');
        $request->server->set('REMOTE_ADDR', '10.0.0.42');
        $stack = new RequestStack();
        $stack->push($request);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $processor = new ContextProcessor($stack, $security, 'prod');

        $record = $processor($this->makeRecord());

        self::assertSame('POST', $record->extra['request_method']);
        self::assertSame('/api/clients', $record->extra['request_uri']);
        self::assertSame('10.0.0.42', $record->extra['client_ip']);
        self::assertSame('prod', $record->extra['environment']);
        self::assertArrayHasKey('request_id', $record->extra);
        self::assertArrayNotHasKey('user_email', $record->extra);
    }

    public function testHttpContextEnrichmentWithAuthenticatedUser(): void
    {
        $request = Request::create('/api/me', 'GET');
        $stack = new RequestStack();
        $stack->push($request);

        $user = new class implements UserInterface {
            public function getRoles(): array
            {
                return ['ROLE_USER'];
            }

            public function eraseCredentials(): void
            {
            }

            public function getUserIdentifier(): string
            {
                return 'alice@example.org';
            }

            public function getId(): int
            {
                return 42;
            }
        };

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($user);

        $processor = new ContextProcessor($stack, $security, 'prod');

        $record = $processor($this->makeRecord());

        self::assertSame('alice@example.org', $record->extra['user_email']);
        self::assertSame(42, $record->extra['user_id']);
    }

    public function testRequestIdIsStableAcrossInvocationsForSameRequest(): void
    {
        $request = Request::create('/');
        $stack = new RequestStack();
        $stack->push($request);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $processor = new ContextProcessor($stack, $security, 'prod');

        $first = $processor($this->makeRecord());
        $second = $processor($this->makeRecord());

        self::assertSame(
            $first->extra['request_id'],
            $second->extra['request_id'],
            'request_id doit être stable au sein du même cycle de requête (correlation ID)',
        );
    }

    public function testCliFallbackContextWhenNoRequest(): void
    {
        $stack = new RequestStack(); // empty stack → CLI / worker context

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $processor = new ContextProcessor($stack, $security, 'prod');

        $record = $processor($this->makeRecord());

        self::assertSame('cli', $record->extra['context']);
        self::assertSame('prod', $record->extra['environment']);
        self::assertArrayNotHasKey('request_id', $record->extra);
        self::assertArrayNotHasKey('request_method', $record->extra);
    }

    public function testExistingExtraDataIsPreserved(): void
    {
        $request = Request::create('/');
        $stack = new RequestStack();
        $stack->push($request);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $processor = new ContextProcessor($stack, $security, 'prod');

        $original = new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'app',
            level: Level::Info,
            message: 'test',
            context: [],
            extra: ['custom_key' => 'custom_value'],
        );

        $record = $processor($original);

        self::assertSame('custom_value', $record->extra['custom_key']);
        self::assertArrayHasKey('request_id', $record->extra);
    }

    public function testReusesRequestIdFromSubscriberWhenAvailable(): void
    {
        // US-096 sync : request_id stocké par RequestIdSubscriber dans
        // Request attributes doit être réutilisé par ContextProcessor (pas
        // de génération locale).
        $request = Request::create('/');
        $request->attributes->set(RequestIdSubscriber::ATTRIBUTE_NAME, 'cf-edge-correlation-42');

        $stack = new RequestStack();
        $stack->push($request);

        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn(null);

        $processor = new ContextProcessor($stack, $security, 'prod');

        $record = $processor($this->makeRecord());

        self::assertSame('cf-edge-correlation-42', $record->extra['request_id']);
    }

    private function makeRecord(): LogRecord
    {
        return new LogRecord(
            datetime: new DateTimeImmutable(),
            channel: 'app',
            level: Level::Info,
            message: 'test message',
            context: [],
            extra: [],
        );
    }
}
