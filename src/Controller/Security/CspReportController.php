<?php

declare(strict_types=1);

namespace App\Controller\Security;

use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller pour recevoir et logger les violations CSP (Content Security Policy).
 *
 * Les navigateurs envoient automatiquement des rapports à /csp/report
 * lorsqu'une violation de la politique CSP est détectée.
 */
#[Route('/csp')]
class CspReportController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Reçoit et logue les rapports de violations CSP.
     *
     * Format du rapport envoyé par le navigateur:
     * {
     *   "csp-report": {
     *     "document-uri": "https://example.com/page",
     *     "violated-directive": "script-src 'self'",
     *     "blocked-uri": "https://evil.com/malicious.js",
     *     "source-file": "https://example.com/page",
     *     "line-number": 10,
     *     "column-number": 5
     *   }
     * }
     */
    #[Route('/report', name: 'csp_report', methods: ['POST'])]
    public function report(Request $request): Response
    {
        $content = $request->getContent();

        if (empty($content)) {
            return new JsonResponse(['status' => 'error', 'message' => 'Empty report'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $report = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            if (!isset($report['csp-report'])) {
                return new JsonResponse(['status' => 'error', 'message' => 'Invalid CSP report format'], Response::HTTP_BAD_REQUEST);
            }

            $cspReport = $report['csp-report'];

            // Logger la violation CSP avec toutes les informations
            $this->logger->warning('CSP Violation detected', [
                'document_uri'       => $cspReport['document-uri']       ?? 'unknown',
                'violated_directive' => $cspReport['violated-directive'] ?? 'unknown',
                'blocked_uri'        => $cspReport['blocked-uri']        ?? 'unknown',
                'source_file'        => $cspReport['source-file']        ?? null,
                'line_number'        => $cspReport['line-number']        ?? null,
                'column_number'      => $cspReport['column-number']      ?? null,
                'user_agent'         => $request->headers->get('User-Agent'),
                'ip'                 => $request->getClientIp(),
            ]);

            return new JsonResponse(['status' => 'ok'], Response::HTTP_NO_CONTENT);
        } catch (JsonException $e) {
            $this->logger->error('Failed to parse CSP report', [
                'error'   => $e->getMessage(),
                'content' => $content,
            ]);

            return new JsonResponse(['status' => 'error', 'message' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }
    }
}
