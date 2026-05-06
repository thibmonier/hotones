<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\LoginSecuritySubscriber;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * Unit tests for LoginSecuritySubscriber.
 *
 * Brute-force protection layer: rate-limits login attempts per IP and
 * audits both failures and successes. Critical to detect bypass or
 * silent loss of audit trail.
 *
 * RateLimiterFactory is `final`, so we use a real factory backed by
 * InMemoryStorage instead of a mock — exercises the real sliding-window
 * algorithm while keeping the test deterministic.
 */
final class LoginSecuritySubscriberTest extends TestCase
{
    private RateLimiterFactory $loginLimiter;
    private LoggerInterface&MockObject $logger;
    private RequestStack&Stub $requestStack;
    private LoginSecuritySubscriber $subscriber;

    protected function setUp(): void
    {
        $this->loginLimiter = new RateLimiterFactory(
            [
                'id' => 'login',
                'policy' => 'sliding_window',
                'limit' => 5,
                'interval' => '15 minutes',
            ],
            new InMemoryStorage(),
        );
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestStack = $this->createStub(RequestStack::class);
        $this->subscriber = new LoginSecuritySubscriber(
            $this->loginLimiter,
            $this->logger,
            $this->requestStack,
        );
    }

    #[Test]
    public function getSubscribedEventsListsLoginFailureAndSuccess(): void
    {
        $events = LoginSecuritySubscriber::getSubscribedEvents();

        self::assertArrayHasKey(LoginFailureEvent::class, $events);
        self::assertArrayHasKey(LoginSuccessEvent::class, $events);
        self::assertSame('onLoginFailure', $events[LoginFailureEvent::class]);
        self::assertSame('onLoginSuccess', $events[LoginSuccessEvent::class]);
    }

    #[Test]
    public function onLoginFailureReturnsSilentlyWhenNoCurrentRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->logger->expects(self::never())->method('warning');

        $this->subscriber->onLoginFailure($this->makeFailureEvent());
    }

    #[Test]
    public function onLoginFailureLogsWarningWithRemainingAttempts(): void
    {
        $request = $this->makeRequestWithIp('10.0.0.42', username: 'jean@test.com');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Login attempt failed',
                self::callback(static function (array $ctx): bool {
                    return $ctx['username'] === 'jean@test.com'
                        && $ctx['ip'] === '10.0.0.42'
                        // 5 max - 1 consumed = 4 remaining on first attempt
                        && $ctx['remaining_attempts'] === 4;
                }),
            );
        $this->logger->expects(self::never())->method('error');

        $this->subscriber->onLoginFailure($this->makeFailureEvent());
    }

    #[Test]
    public function onLoginFailureThrowsTooManyRequestsAfter5AttemptsFromSameIp(): void
    {
        $request = $this->makeRequestWithIp('10.0.0.66', username: 'attacker');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        // Burn the first 5 attempts (limit = 5). They should all log warning, no error.
        for ($i = 0; $i < 5; ++$i) {
            $this->subscriber->onLoginFailure($this->makeFailureEvent());
        }

        // 6th attempt is rejected by the rate limiter -> error log + exception.
        $this->expectException(TooManyRequestsHttpException::class);

        $this->subscriber->onLoginFailure($this->makeFailureEvent());
    }

    #[Test]
    public function onLoginFailureUsesUnknownForMissingUsernameAndIp(): void
    {
        $request = Request::create('/login', 'POST');
        $request->server->remove('REMOTE_ADDR');

        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->logger
            ->expects(self::once())
            ->method('warning')
            ->with(
                'Login attempt failed',
                self::callback(static fn (array $ctx): bool => $ctx['username'] === 'unknown' && $ctx['ip'] === 'unknown'),
            );

        $this->subscriber->onLoginFailure($this->makeFailureEvent());
    }

    #[Test]
    public function onLoginSuccessReturnsSilentlyWhenNoCurrentRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $this->logger->expects(self::never())->method('info');

        $this->subscriber->onLoginSuccess($this->makeSuccessEvent('alice@test.com'));
    }

    #[Test]
    public function onLoginSuccessLogsInfoWithUserIdentifierAndIp(): void
    {
        $request = $this->makeRequestWithIp('10.0.0.7');
        $this->requestStack->method('getCurrentRequest')->willReturn($request);

        $this->logger
            ->expects(self::once())
            ->method('info')
            ->with(
                'User logged in successfully',
                self::callback(static fn (array $ctx): bool => $ctx['username'] === 'alice@test.com' && $ctx['ip'] === '10.0.0.7'),
            );

        $this->subscriber->onLoginSuccess($this->makeSuccessEvent('alice@test.com'));
    }

    private function makeRequestWithIp(string $ip, string $username = 'jean@test.com'): Request
    {
        $request = new Request(
            request: ['_username' => $username],
            server: ['REMOTE_ADDR' => $ip],
        );
        $request->headers = new HeaderBag(['User-Agent' => 'PHPUnit/12']);

        return $request;
    }

    private function makeFailureEvent(): LoginFailureEvent
    {
        $authenticator = $this->createStub(AuthenticatorInterface::class);

        return new LoginFailureEvent(
            new BadCredentialsException(),
            $authenticator,
            Request::create('/login'),
            null,
            'main',
        );
    }

    private function makeSuccessEvent(string $userIdentifier): LoginSuccessEvent
    {
        $user = $this->createStub(UserInterface::class);
        $user->method('getUserIdentifier')->willReturn($userIdentifier);

        $passport = $this->createStub(Passport::class);
        $passport->method('getUser')->willReturn($user);

        $authenticator = $this->createStub(AuthenticatorInterface::class);

        $token = $this->createStub(\Symfony\Component\Security\Core\Authentication\Token\TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        return new LoginSuccessEvent(
            $authenticator,
            $passport,
            $token,
            Request::create('/login'),
            null,
            'main',
        );
    }
}
