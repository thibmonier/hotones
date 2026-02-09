<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\OrderPaymentSchedule;
use App\Service\InvoiceGeneratorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour générer les factures depuis les échéances de paiement forfait.
 *
 * Usage:
 *   php bin/console app:invoice:generate-from-schedules
 *   php bin/console app:invoice:generate-from-schedules --date=2024-12-01
 */
#[AsCommand(
    name: 'app:invoice:generate-from-schedules',
    description: 'Génère les factures depuis les échéances de paiement forfait',
)]
class GenerateInvoicesFromSchedulesCommand extends Command
{
    public function __construct(
        private readonly InvoiceGeneratorService $invoiceGenerator,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'date',
                null,
                InputOption::VALUE_REQUIRED,
                'Date de référence (format YYYY-MM-DD). Par défaut : aujourd\'hui',
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans enregistrement en base')
            ->addOption('skip-existing', null, InputOption::VALUE_NONE, 'Ignorer les échéances déjà facturées')
            ->setHelp(<<<'HELP'
                Cette commande génère automatiquement les factures depuis les échéances de paiement forfait dont la date est arrivée.

                Exemples d'utilisation:
                  <info>php bin/console app:invoice:generate-from-schedules</info>
                  <info>php bin/console app:invoice:generate-from-schedules --date=2024-12-01</info>
                  <info>php bin/console app:invoice:generate-from-schedules --dry-run</info>
                  <info>php bin/console app:invoice:generate-from-schedules --skip-existing</info>

                Les échéances éligibles sont celles dont:
                - La date de facturation est <= à la date de référence
                - Le devis est signé (statut 'signe')
                - Aucune facture n'existe déjà (si --skip-existing)
                HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Déterminer la date de référence
        $dateOption = $input->getOption('date');
        if ($dateOption) {
            try {
                $referenceDate = new DateTime($dateOption);
            } catch (Exception) {
                $io->error(sprintf('Format de date invalide : %s. Utilisez le format YYYY-MM-DD.', $dateOption));

                return Command::FAILURE;
            }
        } else {
            $referenceDate = new DateTime();
        }

        $isDryRun     = $input->getOption('dry-run');
        $skipExisting = $input->getOption('skip-existing');

        $io->title('Génération des factures depuis échéances forfait');
        $io->info(sprintf('Date de référence : %s', $referenceDate->format('d/m/Y')));
        if ($isDryRun) {
            $io->warning('Mode DRY-RUN : aucune donnée ne sera enregistrée en base');
        }

        // Récupérer les échéances éligibles
        $qb = $this->entityManager
            ->getRepository(OrderPaymentSchedule::class)
            ->createQueryBuilder('s')
            ->join('s.order', 'o')
            ->where('s.billingDate <= :date')
            ->andWhere('o.status = :status')
            ->setParameter('date', $referenceDate)
            ->setParameter('status', 'signe')
            ->orderBy('s.billingDate', 'ASC');

        $schedules = $qb->getQuery()->getResult();

        if (empty($schedules)) {
            $io->warning('Aucune échéance éligible trouvée.');

            return Command::SUCCESS;
        }

        $io->section(sprintf('%d échéance(s) trouvée(s)', count($schedules)));

        // Générer les factures
        $invoices = [];
        $skipped  = 0;

        $io->progressStart(count($schedules));

        foreach ($schedules as $schedule) {
            // Vérifier si une facture existe déjà
            if ($skipExisting && $this->invoiceGenerator->invoiceExistsForPaymentSchedule($schedule)) {
                ++$skipped;
                $io->progressAdvance();

                continue;
            }

            try {
                $invoice    = $this->invoiceGenerator->generateFromOrderPaymentSchedule($schedule, !$isDryRun);
                $invoices[] = $invoice;
            } catch (Exception $e) {
                $io->error(sprintf(
                    'Erreur lors de la génération pour l\'échéance #%d : %s',
                    $schedule->getId(),
                    $e->getMessage(),
                ));
            }

            $io->progressAdvance();
        }

        $io->progressFinish();

        // Afficher le résumé
        if (empty($invoices)) {
            if ($skipped > 0) {
                $io->warning(sprintf('Aucune facture générée. %d échéance(s) déjà facturée(s) ignorée(s).', $skipped));
            } else {
                $io->warning('Aucune facture générée.');
            }

            return Command::SUCCESS;
        }

        $io->success(sprintf('%d facture(s) générée(s) avec succès', count($invoices)));
        if ($skipped > 0) {
            $io->info(sprintf('%d échéance(s) déjà facturée(s) ignorée(s)', $skipped));
        }

        $rows     = [];
        $totalHt  = '0.00';
        $totalTtc = '0.00';

        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->getInvoiceNumber(),
                $invoice->getOrder()?->getOrderNumber() ?? 'N/A',
                $invoice->getProject()?->getName()      ?? 'N/A',
                $invoice->getClient()->getName(),
                number_format((float) $invoice->getAmountHt(), 2, ',', ' ').' €',
                number_format((float) $invoice->getAmountTtc(), 2, ',', ' ').' €',
                $invoice->getStatus(),
            ];

            $totalHt  = bcadd($totalHt, $invoice->getAmountHt(), 2);
            $totalTtc = bcadd($totalTtc, $invoice->getAmountTtc(), 2);
        }

        $io->table([
            'Numéro facture',
            'Numéro devis',
            'Projet',
            'Client',
            'Montant HT',
            'Montant TTC',
            'Statut',
        ], $rows);

        $io->horizontalTable(['Total HT', 'Total TTC'], [[
            number_format((float) $totalHt, 2, ',', ' ').' €',
            number_format((float) $totalTtc, 2, ',', ' ').' €',
        ]]);

        if (!$isDryRun) {
            $io->note('Les factures ont été créées avec le statut "brouillon". Pensez à les valider avant envoi.');
        }

        return Command::SUCCESS;
    }
}
