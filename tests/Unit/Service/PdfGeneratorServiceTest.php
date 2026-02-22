<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PdfGeneratorService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class PdfGeneratorServiceTest extends TestCase
{
    private PdfGeneratorService $service;
    private Environment $twig;

    protected function setUp(): void
    {
        $this->twig    = $this->createMock(Environment::class);
        $this->service = new PdfGeneratorService($this->twig);
    }

    public function testGeneratePdfReturnsNonEmptyString(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body><h1>Test PDF</h1></body></html>');

        $result = $this->service->generatePdf('template.html.twig', ['title' => 'Test']);

        $this->assertNotEmpty($result);
        $this->assertIsString($result);
    }

    public function testGeneratePdfStartsWithPdfSignature(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body>Content</body></html>');

        $result = $this->service->generatePdf('template.html.twig', []);

        $this->assertStringStartsWith('%PDF', $result);
    }

    public function testCreatePdfResponseReturnsResponse(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body>Test</body></html>');

        $response = $this->service->createPdfResponse('template.html.twig', [], 'test.pdf');

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testCreatePdfResponseSetsContentType(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body>Test</body></html>');

        $response = $this->service->createPdfResponse('template.html.twig', [], 'test.pdf');

        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
    }

    public function testCreatePdfResponseInlineDisposition(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body>Test</body></html>');

        $response = $this->service->createPdfResponse('template.html.twig', [], 'preview.pdf', inline: true);

        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('preview.pdf', $response->headers->get('Content-Disposition'));
    }

    public function testCreatePdfResponseAttachmentDisposition(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body>Test</body></html>');

        $response = $this->service->createPdfResponse('template.html.twig', [], 'download.pdf', inline: false);

        $this->assertStringContainsString('attachment', $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('download.pdf', $response->headers->get('Content-Disposition'));
    }

    public function testGeneratePdfPassesDataToTemplate(): void
    {
        $data = ['title' => 'My Quote', 'amount' => 1000];

        $this->twig->expects($this->once())
            ->method('render')
            ->with('order/pdf.html.twig', $data)
            ->willReturn('<html><body>My Quote - 1000</body></html>');

        $this->service->generatePdf('order/pdf.html.twig', $data);
    }

    public function testGeneratePdfWithCustomOptions(): void
    {
        $this->twig->method('render')
            ->willReturn('<html><body>Landscape PDF</body></html>');

        $result = $this->service->generatePdf('template.html.twig', [], [
            'orientation' => 'landscape',
            'paper'       => 'A4',
        ]);

        $this->assertNotEmpty($result);
        $this->assertStringStartsWith('%PDF', $result);
    }
}
