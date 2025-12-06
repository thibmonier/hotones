<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Client;
use App\Entity\Invoice;
use App\Entity\Project;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-test-invoices',
    description: 'Génère des factures de test pour le dashboard de trésorerie',
)]
class GenerateTestInvoicesCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('count', 'c', InputOption::VALUE_REQUIRED, 'Nombre de factures à générer', 20);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Génération de factures de test pour la trésorerie');

        $count = (int) $input->getOption('count');

        // Récupérer clients et projets
        $clients  = $this->em->getRepository(Client::class)->findAll();
        $projects = $this->em->getRepository(Project::class)->findAll();

        if (count($clients) === 0) {
            $io->error('Aucun client trouvé. Exécutez d\'abord app:create-test-data --with-test-data');

            return Command::FAILURE;
        }

        if (count($projects) === 0) {
            $io->error('Aucun projet trouvé. Exécutez d\'abord app:create-test-data --with-test-data');

            return Command::FAILURE;
        }

        $statuses = [
            Invoice::STATUS_SENT    => 40,     // 40% envoyées
            Invoice::STATUS_PAID    => 45,     // 45% payées
            Invoice::STATUS_OVERDUE => 15,  // 15% en retard
        ];

        $today        = new DateTime();
        $createdCount = 0;

        $io->section(sprintf('Génération de %d factures', $count));

        for ($i = 0; $i < $count; ++$i) {
            $invoice = new Invoice();

            // Client et projet aléatoires
            $client  = $clients[array_rand($clients)];
            $project = $projects[array_rand($projects)];

            $invoice->setClient($client);
            $invoice->setProject($project);

            // Numéro de facture unique
            $invoiceNumber = sprintf('FAC-TEST-%s-%03d', $today->format('Ym'), $i + 1 + time() % 100);
            $invoice->setInvoiceNumber($invoiceNumber);

            // Montant aléatoire entre 2000€ et 25000€
            $amountHt  = rand(2000, 25000);
            $tvaRate   = 20.00;
            $amountTva = $amountHt * $tvaRate / 100;
            $amountTtc = $amountHt + $amountTva;

            $invoice->setAmountHt((string) $amountHt);
            $invoice->setAmountTva((string) $amountTva);
            $invoice->setTvaRate((string) $tvaRate);
            $invoice->setAmountTtc((string) $amountTtc);

            // Date d'émission entre -6 mois et maintenant
            $monthsAgo = rand(0, 6);
            $issuedAt  = (clone $today)->modify("-{$monthsAgo} months")->modify(sprintf('-%d days', rand(0, 28)));
            $invoice->setIssuedAt($issuedAt);

            // Statut pondéré
            $rand   = rand(1, 100);
            $cumul  = 0;
            $status = Invoice::STATUS_SENT;
            foreach ($statuses as $st => $weight) {
                $cumul += $weight;
                if ($rand <= $cumul) {
                    $status = $st;
                    break;
                }
            }
            $invoice->setStatus($status);

            // Date d'échéance : 30 jours après émission
            $dueDate = (clone $issuedAt)->modify('+30 days');
            $invoice->setDueDate($dueDate);

            // Si payée, date de paiement entre échéance -10j et échéance +5j
            if ($status === Invoice::STATUS_PAID) {
                $daysVariation = rand(-10, 5);
                $paidAt        = (clone $dueDate)->modify(sprintf('%+d days', $daysVariation));
                $invoice->setPaidAt($paidAt);
            }

            $this->em->persist($invoice);
            ++$createdCount;

            if ($createdCount % 10 === 0) {
                $this->em->flush();
                $io->write('.');
            }
        }

        $this->em->flush();

        $io->newLine();
        $io->success(sprintf('✅ %d factures générées avec succès', $createdCount));

        // Statistiques
        $io->section('Répartition par statut');
        foreach ($statuses as $status => $weight) {
            $count = $this->em->getRepository(Invoice::class)->count(['status' => $status]);
            $io->writeln(sprintf('  • %s : %d factures (%d%%)', ucfirst($status), $count, $weight));
        }

        $io->note('Vous pouvez maintenant accéder au dashboard de trésorerie : /treasury/dashboard');

        return Command::SUCCESS;
    }
}
