<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\SyncBoondManagerTimesMessage;
use App\Repository\BoondManagerSettingsRepository;
use App\Service\BoondManager\BoondManagerClient;
use App\Service\BoondManager\BoondManagerSyncService;
use DateTime;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:sync-boond-manager',
    description: 'Synchronise les temps passes depuis BoondManager',
    aliases: ['hotones:sync-boond'],
)]
class SyncBoondManagerCommand extends Command
{
    public function __construct(
        private readonly BoondManagerSyncService $syncService,
        private readonly BoondManagerClient $client,
        private readonly BoondManagerSettingsRepository $settingsRepository,
        private readonly MessageBusInterface $messageBus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'start-date',
                's',
                InputOption::VALUE_OPTIONAL,
                'Date de debut (YYYY-MM-DD)',
                new DateTime('-30 days')->format('Y-m-d'),
            )
            ->addOption(
                'end-date',
                'f',
                InputOption::VALUE_OPTIONAL,
                'Date de fin (YYYY-MM-DD)',
                new DateTime()->format('Y-m-d'),
            )
            ->addOption(
                'sync-resources',
                null,
                InputOption::VALUE_NONE,
                'Synchroniser aussi les ressources (contributeurs)',
            )
            ->addOption('sync-projects', null, InputOption::VALUE_NONE, 'Synchroniser aussi les projets')
            ->addOption('test-connection', 't', InputOption::VALUE_NONE, 'Tester uniquement la connexion a l\'API')
            ->addOption('async', 'a', InputOption::VALUE_NONE, 'Executer la synchronisation en arriere-plan')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Synchroniser toutes les entreprises configurees')
            ->setHelp(<<<'HELP'
                Cette commande synchronise les temps passes depuis BoondManager vers HotOnes.

                Exemples :
                  app:sync-boond-manager                           # Sync des 30 derniers jours
                  app:sync-boond-manager -s 2024-01-01 -f 2024-01-31  # Sync de janvier 2024
                  app:sync-boond-manager --sync-resources          # Sync avec les contributeurs
                  app:sync-boond-manager --sync-projects           # Sync avec les projets
                  app:sync-boond-manager --test-connection         # Test de connexion uniquement
                  app:sync-boond-manager --async                   # Execution asynchrone
                  app:sync-boond-manager --all                     # Toutes les entreprises configurees

                Configuration requise dans l'interface d'administration :
                  /admin/boond-manager

                Documentation BoondManager API :
                  https://doc.boondmanager.com/api-externe/
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $startDateStr   = $input->getOption('start-date');
        $endDateStr     = $input->getOption('end-date');
        $syncResources  = $input->getOption('sync-resources');
        $syncProjects   = $input->getOption('sync-projects');
        $testConnection = $input->getOption('test-connection');
        $async          = $input->getOption('async');
        $all            = $input->getOption('all');

        try {
            $startDate = new DateTime($startDateStr);
            $endDate   = new DateTime($endDateStr);
        } catch (Exception $e) {
            $io->error('Format de date invalide. Utilisez YYYY-MM-DD');

            return Command::FAILURE;
        }

        try {
            if ($all) {
                return $this->syncAllCompanies(
                    $io,
                    $startDate,
                    $endDate,
                    $async,
                    $syncResources,
                    $syncProjects,
                    $testConnection,
                );
            }

            return $this->syncCurrentCompany(
                $io,
                $startDate,
                $endDate,
                $async,
                $syncResources,
                $syncProjects,
                $testConnection,
            );
        } catch (Exception $e) {
            $io->error('Erreur: '.$e->getMessage());

            if ($output->isVerbose()) {
                $io->writeln('Stack trace:');
                $io->writeln($e->getTraceAsString());
            }

            return Command::FAILURE;
        }
    }

    private function syncAllCompanies(
        SymfonyStyle $io,
        DateTime $startDate,
        DateTime $endDate,
        bool $async,
        bool $syncResources,
        bool $syncProjects,
        bool $testConnection,
    ): int {
        $settingsList = $this->settingsRepository->findNeedingSync();

        if (empty($settingsList)) {
            $io->warning('Aucune entreprise configuree pour la synchronisation BoondManager');

            return Command::SUCCESS;
        }

        $io->info(sprintf('Synchronisation de %d entreprise(s)...', count($settingsList)));

        $successCount = 0;
        $errorCount   = 0;

        foreach ($settingsList as $settings) {
            $companyName = $settings->getCompany()->getName();
            $io->section("Entreprise: {$companyName}");

            if ($testConnection) {
                $isConnected = $this->client->testConnection($settings);
                if ($isConnected) {
                    $io->success('Connexion OK');
                    ++$successCount;
                } else {
                    $io->error('Connexion echouee');
                    ++$errorCount;
                }

                continue;
            }

            if ($async) {
                $this->messageBus->dispatch(
                    new SyncBoondManagerTimesMessage(
                        $settings->getCompany()->getId(),
                        $startDate->format('Y-m-d'),
                        $endDate->format('Y-m-d'),
                    ),
                );
                $io->writeln('  > Synchronisation envoyee en arriere-plan');
                ++$successCount;

                continue;
            }

            // Sync synchrone
            if ($syncResources) {
                $resourceResult = $this->syncService->syncResources($settings);
                $io->writeln(sprintf('  > Ressources: %s', $resourceResult->getSummary()));
            }

            if ($syncProjects) {
                $projectResult = $this->syncService->syncProjects($settings);
                $io->writeln(sprintf('  > Projets: %s', $projectResult->getSummary()));
            }

            $result = $this->syncService->sync($settings, $startDate, $endDate);

            if ($result->success) {
                $io->writeln(sprintf('  > Temps: %s', $result->getSummary()));
                ++$successCount;
            } else {
                $io->error(sprintf('  > Erreur: %s', $result->error));
                ++$errorCount;
            }
        }

        $io->newLine();

        if ($errorCount > 0) {
            $io->warning(sprintf('Termine avec %d succes et %d erreurs', $successCount, $errorCount));

            return Command::FAILURE;
        }

        $io->success(sprintf('Synchronisation terminee pour %d entreprise(s)', $successCount));

        return Command::SUCCESS;
    }

    private function syncCurrentCompany(
        SymfonyStyle $io,
        DateTime $startDate,
        DateTime $endDate,
        bool $async,
        bool $syncResources,
        bool $syncProjects,
        bool $testConnection,
    ): int {
        $settings = $this->settingsRepository->getSettings();

        if (!$settings->isConfigured()) {
            $io->error('BoondManager n\'est pas configure. Configurez-le dans /admin/boond-manager');

            return Command::FAILURE;
        }

        if (!$settings->enabled) {
            $io->warning('La synchronisation BoondManager est desactivee.');

            return Command::SUCCESS;
        }

        $io->title('Synchronisation BoondManager');
        $io->table(['Parametre', 'Valeur'], [
            ['URL API', $settings->apiBaseUrl ?? 'Non configure'],
            ['Type auth', $settings->authType],
            ['Date debut', $startDate->format('Y-m-d')],
            ['Date fin', $endDate->format('Y-m-d')],
        ]);

        if ($testConnection) {
            $io->info('Test de connexion...');
            $isConnected = $this->client->testConnection($settings);

            if ($isConnected) {
                $io->success('Connexion a l\'API BoondManager reussie !');

                return Command::SUCCESS;
            }

            $io->error('Echec de connexion a l\'API BoondManager. Verifiez vos identifiants.');

            return Command::FAILURE;
        }

        if ($async) {
            $io->info('Envoi de la synchronisation en arriere-plan...');

            $this->messageBus->dispatch(
                new SyncBoondManagerTimesMessage(
                    $settings->getCompany()->getId(),
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ),
            );

            $io->success('Synchronisation envoyee. Executez `messenger:consume async` pour la traiter.');

            return Command::SUCCESS;
        }

        // Synchronisation synchrone
        if ($syncResources) {
            $io->section('Synchronisation des ressources...');
            $resourceResult = $this->syncService->syncResources($settings);
            $io->writeln($resourceResult->getSummary());

            if (!empty($resourceResult->errors)) {
                foreach (array_slice($resourceResult->errors, 0, 5) as $error) {
                    $io->writeln('  - '.$error);
                }
            }
        }

        if ($syncProjects) {
            $io->section('Synchronisation des projets...');
            $projectResult = $this->syncService->syncProjects($settings);
            $io->writeln($projectResult->getSummary());

            if (!empty($projectResult->errors)) {
                foreach (array_slice($projectResult->errors, 0, 5) as $error) {
                    $io->writeln('  - '.$error);
                }
            }
        }

        $io->section('Synchronisation des temps passes...');
        $result = $this->syncService->sync($settings, $startDate, $endDate);

        if ($result->success) {
            $io->success($result->getSummary());

            $io->table(['Metrique', 'Valeur'], [
                ['Crees', $result->created],
                ['Mis a jour', $result->updated],
                ['Ignores', $result->skipped],
                ['Total', $result->getTotal()],
            ]);

            if (!empty($result->errors)) {
                $io->warning('Quelques erreurs rencontrees:');
                foreach (array_slice($result->errors, 0, 10) as $error) {
                    $io->writeln('  - '.$error);
                }
            }

            return Command::SUCCESS;
        }

        $io->error('Echec de la synchronisation: '.$result->error);

        if (!empty($result->errors)) {
            $io->section('Erreurs detaillees:');
            foreach ($result->errors as $error) {
                $io->writeln('  - '.$error);
            }
        }

        return Command::FAILURE;
    }
}
