<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\AnalyzeProjectRisksMessage;
use App\Service\ProjectRiskAnalyzer;
use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class AnalyzeProjectRisksMessageHandler
{
    public function __construct(
        private readonly ProjectRiskAnalyzer $riskAnalyzer,
        private readonly LoggerInterface $logger
    ) {
    }

    public function __invoke(AnalyzeProjectRisksMessage $message): void
    {
        $this->logger->info('[Scheduler] Début de l\'analyse des risques projets');

        try {
            $healthScores = $this->riskAnalyzer->analyzeAllActiveProjects();

            $healthy  = 0;
            $warning  = 0;
            $critical = 0;

            foreach ($healthScores as $healthScore) {
                match ($healthScore->getHealthLevel()) {
                    'healthy'  => $healthy++,
                    'warning'  => $warning++,
                    'critical' => $critical++,
                    default    => null,
                };
            }

            $this->logger->info('[Scheduler] Analyse des risques terminée', [
                'total_projects' => count($healthScores),
                'healthy'        => $healthy,
                'warning'        => $warning,
                'critical'       => $critical,
            ]);
        } catch (Exception $e) {
            $this->logger->error('[Scheduler] Erreur lors de l\'analyse des risques', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
