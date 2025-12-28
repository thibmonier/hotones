<?php

declare(strict_types=1);

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for handling CSP (Content Security Policy) violation reports.
 *
 * CSP violations are automatically reported by browsers when they detect
 * attempts to load resources that violate the configured CSP policy.
 * This helps identify XSS attacks or misconfigured CSP directives.
 */
class CspReportController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Receives and logs CSP violation reports from browsers.
     *
     * Browsers send a JSON payload with details about the violation:
     * - document-uri: The URL of the page where the violation occurred
     * - violated-directive: Which CSP directive was violated
     * - blocked-uri: The URI of the resource that was blocked
     * - source-file, line-number, column-number: Location of the violation
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTTP/CSP#violation_reports
     */
    #[Route('/csp/report', name: 'csp_report', methods: ['POST'])]
    public function report(Request $request): Response
    {
        $content = $request->getContent();

        // Decode the JSON payload
        $report = json_decode($content, true);

        if (!$report || !isset($report['csp-report'])) {
            $this->logger->warning('Invalid CSP report received', [
                'content' => $content,
                'ip'      => $request->getClientIp(),
            ]);

            return new Response('', Response::HTTP_BAD_REQUEST);
        }

        $cspReport = $report['csp-report'];

        // Log the violation
        $this->logger->warning('CSP violation detected', [
            'document_uri'       => $cspReport['document-uri']             ?? 'unknown',
            'violated_directive' => $cspReport['violated-directive'] ?? 'unknown',
            'blocked_uri'        => $cspReport['blocked-uri']               ?? 'unknown',
            'source_file'        => $cspReport['source-file']               ?? null,
            'line_number'        => $cspReport['line-number']               ?? null,
            'column_number'      => $cspReport['column-number']           ?? null,
            'original_policy'    => $cspReport['original-policy']       ?? null,
            'user_agent'         => $request->headers->get('User-Agent'),
            'ip'                 => $request->getClientIp(),
        ]);

        // Return 204 No Content (standard response for CSP reports)
        return new Response('', Response::HTTP_NO_CONTENT);
    }
}
