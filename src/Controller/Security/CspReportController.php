<?php

declare(strict_types=1);

namespace App\Controller\Security;

use JsonException;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        private readonly LoggerInterface $logger,
        #[Autowire('%kernel.environment%')]
        private readonly string $environment,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
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
                return new JsonResponse([
                    'status'  => 'error',
                    'message' => 'Invalid CSP report format',
                ], Response::HTTP_BAD_REQUEST);
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

    /**
     * Affiche les violations CSP récentes (dev only).
     *
     * Endpoint de debug pour visualiser les violations CSP loggées.
     * Disponible uniquement en environnement de développement.
     */
    #[Route('/violations', name: 'csp_violations', methods: ['GET'])]
    public function violations(): Response
    {
        // Disponible uniquement en dev
        if ($this->environment !== 'dev') {
            throw new NotFoundHttpException('This endpoint is only available in dev environment');
        }

        $logFile = $this->projectDir.'/var/log/dev.log';

        if (!file_exists($logFile)) {
            return $this->render('security/csp_violations.html.twig', [
                'violations' => [],
                'error'      => 'Log file not found',
            ]);
        }

        // Lire les 1000 dernières lignes du log (pour performance)
        $lines = $this->tail($logFile, 1000);

        $violations = [];
        foreach ($lines as $line) {
            if (!str_contains($line, 'CSP Violation detected')) {
                continue;
            }
            // Parser la ligne de log pour extraire les infos
            if (!preg_match('/\[(.*?)\].*CSP Violation detected.*context:\s*({.*})/s', $line, $matches)) {
                continue;
            }
            try {
                $context      = json_decode($matches[2], true, 512, JSON_THROW_ON_ERROR);
                $violations[] = [
                    'timestamp'          => $matches[1],
                    'document_uri'       => $context['document_uri']       ?? 'unknown',
                    'violated_directive' => $context['violated_directive'] ?? 'unknown',
                    'blocked_uri'        => $context['blocked_uri']        ?? 'unknown',
                    'source_file'        => $context['source_file']        ?? null,
                    'line_number'        => $context['line_number']        ?? null,
                ];
            } catch (JsonException) {
                // Ignorer les lignes mal formées
                continue;
            }
        }

        // Inverser pour avoir les plus récentes en premier
        $violations = array_reverse($violations);

        return $this->render('security/csp_violations.html.twig', [
            'violations' => $violations,
            'error'      => null,
        ]);
    }

    /**
     * Lire les dernières lignes d'un fichier (équivalent tail -n).
     *
     * @return array<int, string>
     */
    private function tail(string $filepath, int $lines = 100): array
    {
        $handle = fopen($filepath, 'r');
        if (!$handle) {
            return [];
        }

        $buffer = 4096;
        fseek($handle, -1, SEEK_END);
        $output = '';
        $chunk  = '';

        while (ftell($handle) > 0 && substr_count($output, "\n") < $lines) {
            $seek = min(ftell($handle), $buffer);
            fseek($handle, -$seek, SEEK_CUR);
            $chunk  = fread($handle, $seek) ?: '';
            $output = $chunk.$output;
            fseek($handle, -mb_strlen($chunk, '8bit'), SEEK_CUR);
        }

        fclose($handle);

        $output = explode("\n", $output);

        return array_slice($output, -$lines);
    }
}
