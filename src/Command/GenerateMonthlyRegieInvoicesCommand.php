<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\InvoiceGeneratorService;
use DateTime;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour générer les factures mensuelles régie.
 *
 * Usage:
 *   php bin/console app:invoice:generate-monthly-regie 2024-12
 *   php bin/console app:invoice:generate-monthly-regie --last-month
 */
#[AsCommand(
    name: 'app:invoice:generate-monthly-regie',
    description: 'Génère les factures mensuelles pour les projets en régie',
)]
class GenerateMonthlyRegieInvoicesCommand extends Command
{
    public function __construct(
        private readonly InvoiceGeneratorService $invoiceGenerator,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('month', InputArgument::OPTIONAL, 'Mois à facturer (format YYYY-MM). Si omis, utilise le mois dernier.')
            ->addOption('last-month', null, InputOption::VALUE_NONE, 'Facturer le mois dernier')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans enregistrement en base')
            ->setHelp(<<<'HELP'
                Cette commande génère automatiquement les factures mensuelles pour tous les projets en régie actifs.

                Exemples d'utilisation:
                  <info>php bin/console app:invoice:generate-monthly-regie 2024-12</info>
                  <info>php bin/console app:invoice:generate-monthly-regie --last-month</info>
                  <info>php bin/console app:invoice:generate-monthly-regie --dry-run</info>

                Les factures générées incluent:
                - Une ligne par contributeur ayant saisi des temps
                - Le calcul du CA basé sur les TJM contributeurs
                - Le statut initial en "brouillon"
                HELP
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Déterminer le mois à facturer
        $monthArg = $input->getArgument('month');
        if ($input->getOption('last-month') || !$monthArg) {
            $month = new DateTime('first day of last month');
        } else {
            try {
                $month = new DateTime($monthArg.'-01');
            } catch (Exception) {
                $io->error(sprintf('Format de date invalide : %s. Utilisez le format YYYY-MM.', $monthArg));

                return Command::FAILURE;
            }
        }

        $isDryRun = $input->getOption('dry-run');

        $io->title('Génération des factures mensuelles régie');
        $io->info(sprintf('Mois à facturer : %s', $month->format('F Y')));
        if ($isDryRun) {
            $io->warning('Mode DRY-RUN : aucune donnée ne sera enregistrée en base');
        }

        // Générer les factures
        $io->section('Génération en cours...');
        $invoices = $this->invoiceGenerator->generateAllMonthlyRegieInvoices($month, !$isDryRun);

        if (empty($invoices)) {
            $io->warning('Aucune facture générée (aucun projet régie actif avec des temps saisis).');

            return Command::SUCCESS;
        }

        // Afficher le résumé
        $io->success(sprintf('%d facture(s) générée(s) avec succès', count($invoices)));

        $rows     = [];
        $totalHt  = '0.00';
        $totalTtc = '0.00';

        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->getInvoiceNumber(),
                $invoice->getProject()?->getName() ?? 'N/A',
                $invoice->getClient()->getName(),
                number_format((float) $invoice->getAmountHt(), 2, ',', ' ').' €',
                number_format((float) $invoice->getAmountTtc(), 2, ',', ' ').' €',
                $invoice->getStatus(),
            ];

            $totalHt  = bcadd($totalHt, $invoice->getAmountHt(), 2);
            $totalTtc = bcadd($totalTtc, $invoice->getAmountTtc(), 2);
        }

        $io->table(
            ['Numéro', 'Projet', 'Client', 'Montant HT', 'Montant TTC', 'Statut'],
            $rows,
        );

        $io->horizontalTable(
            ['Total HT', 'Total TTC'],
            [[
                number_format((float) $totalHt, 2, ',', ' ').' €',
                number_format((float) $totalTtc, 2, ',', ' ').' €',
            ]],
        );

        if (!$isDryRun) {
            $io->note('Les factures ont été créées avec le statut "brouillon". Pensez à les valider avant envoi.');
        }

        return Command::SUCCESS;
    }
}
