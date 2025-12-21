<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\SaasSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:saas:renew-subscriptions',
    description: 'Renouvelle automatiquement les abonnements SaaS échus avec auto-renewal activé',
)]
final class SaasRenewSubscriptionsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly SaasSubscriptionRepository $subscriptionRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche les abonnements à renouveler sans les modifier',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io     = new SymfonyStyle($input, $output);
        $dryRun = (bool) $input->getOption('dry-run');

        if ($dryRun) {
            $io->note('Mode DRY-RUN : Aucune modification ne sera effectuée');
        }

        // Récupérer tous les abonnements qui doivent être renouvelés
        $subscriptionsDue = $this->subscriptionRepository->findDueForRenewal();

        if (empty($subscriptionsDue)) {
            $io->success('Aucun abonnement à renouveler.');

            return Command::SUCCESS;
        }

        $io->title(sprintf(
            '%d abonnement(s) à renouveler',
            count($subscriptionsDue),
        ));

        $renewed = 0;
        $errors  = 0;

        foreach ($subscriptionsDue as $subscription) {
            try {
                $serviceName    = $subscription->getDisplayName();
                $oldRenewalDate = $subscription->getNextRenewalDate();

                $io->section($serviceName);
                $io->text([
                    sprintf('ID: %d', $subscription->getId()),
                    sprintf('Service: %s', $serviceName),
                    sprintf('Prix: %s %s', $subscription->getPrice(), $subscription->getCurrency()),
                    sprintf('Période: %s', $subscription->getBillingPeriod() === 'monthly' ? 'Mensuel' : 'Annuel'),
                    sprintf('Date de renouvellement actuelle: %s', $oldRenewalDate->format('d/m/Y')),
                ]);

                if (!$dryRun) {
                    // Renouveler l'abonnement
                    $subscription->renew();

                    $newRenewalDate = $subscription->getNextRenewalDate();
                    $io->success(sprintf(
                        'Abonnement renouvelé. Prochaine date: %s',
                        $newRenewalDate->format('d/m/Y'),
                    ));

                    ++$renewed;
                } else {
                    // Calculer la future date sans modifier l'entité
                    $futureDate = $subscription->calculateNextRenewalDate($oldRenewalDate);
                    $io->text(sprintf(
                        '[DRY-RUN] Serait renouvelé. Prochaine date: %s',
                        $futureDate->format('d/m/Y'),
                    ));
                    ++$renewed;
                }
            } catch (Exception $e) {
                $io->error(sprintf(
                    'Erreur lors du renouvellement de l\'abonnement %d: %s',
                    $subscription->getId(),
                    $e->getMessage(),
                ));
                ++$errors;
            }
        }

        // Sauvegarder les modifications si pas en mode dry-run
        if (!$dryRun && $renewed > 0) {
            $this->em->flush();
            $io->success(sprintf(
                '%d abonnement(s) renouvelé(s) avec succès',
                $renewed,
            ));
        } elseif ($dryRun && $renewed > 0) {
            $io->note(sprintf(
                '[DRY-RUN] %d abonnement(s) auraient été renouvelés',
                $renewed,
            ));
        }

        if ($errors > 0) {
            $io->warning(sprintf('%d erreur(s) rencontrée(s)', $errors));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
