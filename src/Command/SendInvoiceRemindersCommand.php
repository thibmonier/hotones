<?php

declare(strict_types=1);

namespace App\Command;

use App\Service\InvoiceReminderService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Commande pour envoyer les relances automatiques de factures.
 *
 * Usage:
 *   php bin/console app:invoice:send-reminders
 *   php bin/console app:invoice:send-reminders --dry-run
 */
#[AsCommand(
    name: 'app:invoice:send-reminders',
    description: 'Envoie les relances automatiques pour les factures en retard',
)]
class SendInvoiceRemindersCommand extends Command
{
    public function __construct(
        private readonly InvoiceReminderService $reminderService,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('dry-run', null, InputOption::VALUE_NONE, 'Simulation sans envoi d\'emails')->setHelp(<<<'HELP'
            Cette commande envoie automatiquement des relances par email pour les factures en retard.

            Règles de relance:
              - J+30 : Première relance (courtoise)
              - J+45 : Deuxième relance (ferme)
              - J+60 : Relance finale (mention pénalités)

            Les relances ne sont envoyées qu'une seule fois par palier.

            Exemples d'utilisation:
              <info>php bin/console app:invoice:send-reminders</info>
              <info>php bin/console app:invoice:send-reminders --dry-run</info>

            Cette commande devrait être schedulée quotidiennement via cron ou Symfony Scheduler.
            HELP);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $isDryRun = $input->getOption('dry-run');

        $io->title('Envoi des relances automatiques de factures');
        if ($isDryRun) {
            $io->warning('Mode DRY-RUN : aucun email ne sera envoyé');
        }

        $io->section('Traitement en cours...');

        // Traiter toutes les relances
        $stats = $this->reminderService->processAllReminders($isDryRun);

        // Afficher les résultats
        $io->newLine();
        if ($stats['sent'] > 0) {
            $io->success(sprintf(
                '%d relance(s) %s avec succès',
                $stats['sent'],
                $isDryRun ? 'serai(en)t envoyée(s)' : 'envoyée(s)',
            ));
        }

        if ($stats['skipped'] > 0) {
            $io->info(sprintf('%d facture(s) ignorée(s) (relance déjà envoyée)', $stats['skipped']));
        }

        if ($stats['errors'] > 0) {
            $io->error(sprintf('%d erreur(s) rencontrée(s)', $stats['errors']));

            return Command::FAILURE;
        }

        if ($stats['sent'] === 0 && $stats['skipped'] === 0) {
            $io->info('Aucune relance à envoyer');
        }

        // Afficher les statistiques globales
        $io->section('Statistiques des relances');
        $reminderStats = $this->reminderService->getReminderStats();

        $io->table(['Type de relance', 'Nombre envoyé'], [
            ['J+30 (Première relance)', $reminderStats['by_delay'][30]],
            ['J+45 (Deuxième relance)', $reminderStats['by_delay'][45]],
            ['J+60 (Relance finale)', $reminderStats['by_delay'][60]],
            ['<info>TOTAL</info>', '<info>'.$reminderStats['total_reminders'].'</info>'],
        ]);

        return Command::SUCCESS;
    }
}
