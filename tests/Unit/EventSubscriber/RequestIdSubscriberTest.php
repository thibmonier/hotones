<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber;

use App\EventSubscriber\RequestIdSubscriber;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * US-096 (sprint-018 EPIC-002) — coverage Unit du subscriber correlation ID.
 */
#[AllowMockObjectsWithoutExpectations]
final class RequestIdSubscriberTest extends TestCase
{
    public function testGeneratesRequestIdWhenNoIncomingHeader(): void
    {
        $request = Request::create('/');
        $subscriber = new RequestIdSubscriber();

        $subscriber->onKernelRequest($this->makeRequestEvent($request));

        $stored = $request->attributes->get(RequestIdSubscriber::ATTRIBUTE_NAME);
        self::assertIsString($stored);
        self::assertNotEmpty($stored);
        // Format `<YmdHis>-<8 hex>`
        self::assertMatchesRegularExpression('/^\d{14}-[a-f0-9]{8}$/', $stored);
    }

    public function testReusesIncomingHeaderWhenValid(): void
    {
        $request = Request::create('/');
        $request->headers->set(RequestIdSubscriber::HEADER_NAME, 'cf-edge-abc123');

        $subscriber = new RequestIdSubscriber();
        $subscriber->onKernelRequest($this->makeRequestEvent($request));

        self::assertSame(
            'cf-edge-abc123',
            $request->attributes->get(RequestIdSubscriber::ATTRIBUTE_NAME),
        );
    }

    public function testRejectsIncomingHeaderWithInjectionChars(): void
    {
        $request = Request::create('/');
        // Header injection attempt (CRLF + Set-Cookie)
        $request->headers->set(RequestIdSubscriber::HEADER_NAME, "abc\r\nSet-Cookie: x=y");

        $subscriber = new RequestIdSubscriber();
        $subscriber->onKernelRequest($this->makeRequestEvent($request));

        $stored = $request->attributes->get(RequestIdSubscriber::ATTRIBUTE_NAME);
        self::assertIsString($stored);
        self::assertStringNotContainsString("\r", $stored);
        self::assertStringNotContainsString("\n", $stored);
        self::assertStringNotContainsString('Set-Cookie', $stored);
    }

    public function testRejectsIncomingHeaderTooLong(): void
    {
        $request = Request::create('/');
        $request->headers->set(RequestIdSubscriber::HEADER_NAME, str_repeat('a', 200));

        $subscriber = new RequestIdSubscriber();
        $subscriber->onKernelRequest($this->makeRequestEvent($request));

        $stored = $request->attributes->get(RequestIdSubscriber::ATTRIBUTE_NAME);
        self::assertIsString($stored);
        self::assertLessThanOrEqual(128, strlen($stored));
        self::assertNotSame(str_repeat('a', 200), $stored);
    }

    public function testIgnoresSubRequests(): void
    {
        $request = Request::create('/');
        $subscriber = new RequestIdSubscriber();

        $subscriber->onKernelRequest($this->makeRequestEvent($request, HttpKernelInterface::SUB_REQUEST));

        self::assertNull($request->attributes->get(RequestIdSubscriber::ATTRIBUTE_NAME));
    }

    public function testResponseHeaderIsSetFromRequestAttribute(): void
    {
        $request = Request::create('/');
        $request->attributes->set(RequestIdSubscriber::ATTRIBUTE_NAME, 'test-correlation-42');

        $response = new Response();
        $subscriber = new RequestIdSubscriber();

        $subscriber->onKernelResponse($this->makeResponseEvent($request, $response));

        self::assertSame(
            'test-correlation-42',
            $response->headers->get(RequestIdSubscriber::HEADER_NAME),
        );
    }

    public function testResponseHeaderNotSetIfAttributeMissing(): void
    {
        $request = Request::create('/');
        $response = new Response();
        $subscriber = new RequestIdSubscriber();

        $subscriber->onKernelResponse($this->makeResponseEvent($request, $response));

        self::assertNull($response->headers->get(RequestIdSubscriber::HEADER_NAME));
    }

    public function testResponseHeaderIgnoresSubRequests(): void
    {
        $request = Request::create('/');
        $request->attributes->set(RequestIdSubscriber::ATTRIBUTE_NAME, 'sub-request-id');

        $response = new Response();
        $subscriber = new RequestIdSubscriber();

        $subscriber->onKernelResponse($this->makeResponseEvent($request, $response, HttpKernelInterface::SUB_REQUEST));

        self::assertNull($response->headers->get(RequestIdSubscriber::HEADER_NAME));
    }

    public function testSubscribedEventsCoverRequestAndResponse(): void
    {
        $events = RequestIdSubscriber::getSubscribedEvents();

        self::assertArrayHasKey('kernel.request', $events);
        self::assertArrayHasKey('kernel.response', $events);
    }

    private function makeRequestEvent(
        Request $request,
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): RequestEvent {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new RequestEvent($kernel, $request, $type);
    }

    private function makeResponseEvent(
        Request $request,
        Response $response,
        int $type = HttpKernelInterface::MAIN_REQUEST,
    ): ResponseEvent {
        $kernel = $this->createMock(HttpKernelInterface::class);

        return new ResponseEvent($kernel, $request, $type, $response);
    }
}
