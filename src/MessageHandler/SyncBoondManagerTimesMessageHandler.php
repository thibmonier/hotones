<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\SyncBoondManagerTimesMessage;
use App\Repository\BoondManagerSettingsRepository;
use App\Repository\CompanyRepository;
use App\Service\BoondManager\BoondManagerSyncService;
use DateTime;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class SyncBoondManagerTimesMessageHandler
{
    public function __construct(
        private BoondManagerSyncService $syncService,
        private BoondManagerSettingsRepository $settingsRepository,
        private CompanyRepository $companyRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(SyncBoondManagerTimesMessage $message): void
    {
        $company = $this->companyRepository->find($message->companyId);

        if (!$company) {
            $this->logger->error('SyncBoondManagerTimes: Company not found', [
                'companyId' => $message->companyId,
            ]);

            return;
        }

        // Recuperer les settings pour cette company (sans company context car async)
        $settings = $this->settingsRepository->findOneBy(['company' => $company]);

        if (!$settings) {
            $this->logger->warning('SyncBoondManagerTimes: No settings found for company', [
                'companyId' => $message->companyId,
            ]);

            return;
        }

        $startDate = new DateTime($message->startDate);
        $endDate = new DateTime($message->endDate);

        $result = $this->syncService->sync($settings, $startDate, $endDate);

        $this->logger->info('SyncBoondManagerTimes completed', [
            'companyId' => $message->companyId,
            'success' => $result->success,
            'created' => $result->created,
            'updated' => $result->updated,
            'skipped' => $result->skipped,
            'error' => $result->error,
        ]);
    }
}
