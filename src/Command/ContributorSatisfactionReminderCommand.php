<?php

declare(strict_types=1);

namespace App\Command;

use App\Repository\ContributorRepository;
use App\Repository\ContributorSatisfactionRepository;
use DateTime;
use Exception;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:satisfaction:send-reminders',
    description: 'Envoie des rappels aux collaborateurs qui n\'ont pas saisi leur satisfaction mensuelle',
    aliases: ['hotones:satisfaction:send-reminders'],
)]
class ContributorSatisfactionReminderCommand extends Command
{
    public function __construct(
        private readonly ContributorRepository $contributorRepository,
        private readonly ContributorSatisfactionRepository $satisfactionRepository,
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail,
        private readonly string $fromName,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('year', null, InputOption::VALUE_OPTIONAL, 'Année (par défaut : année en cours)')
            ->addOption('month', null, InputOption::VALUE_OPTIONAL, 'Mois (par défaut : mois en cours)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode simulation (n\'envoie pas les emails)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $now    = new DateTime();
        $year   = (int) ($input->getOption('year') ?? $now->format('Y'));
        $month  = (int) ($input->getOption('month') ?? $now->format('n'));
        $dryRun = (bool) $input->getOption('dry-run');

        $io->title(sprintf('Rappel satisfaction collaborateur - %s %d', $this->getMonthLabel($month), $year));

        if ($dryRun) {
            $io->warning('Mode simulation activé - aucun email ne sera envoyé');
        }

        // Récupérer tous les contributeurs actifs
        $contributors = $this->contributorRepository->findBy(['active' => true]);

        $sent    = 0;
        $skipped = 0;

        foreach ($contributors as $contributor) {
            // Vérifier si le collaborateur a un email et un compte utilisateur
            if (!$contributor->getUser() || !$contributor->getEmail()) {
                ++$skipped;
                continue;
            }

            // Vérifier si le collaborateur a déjà saisi sa satisfaction pour cette période
            $satisfaction = $this->satisfactionRepository->findByContributorAndPeriod($contributor, $year, $month);

            if ($satisfaction) {
                ++$skipped;
                continue;
            }

            // Envoyer le rappel
            if (!$dryRun) {
                try {
                    $this->sendReminder($contributor, $year, $month);
                    ++$sent;
                    $io->text(sprintf('✓ Email envoyé à %s', $contributor->getFullName()));
                } catch (Exception $e) {
                    $io->error(sprintf(
                        'Erreur lors de l\'envoi à %s : %s',
                        $contributor->getFullName(),
                        $e->getMessage(),
                    ));
                }
            } else {
                ++$sent;
                $io->text(sprintf('[DRY-RUN] Email à envoyer à %s', $contributor->getFullName()));
            }
        }

        $io->success(sprintf('%d email(s) envoyé(s), %d collaborateur(s) ignoré(s)', $sent, $skipped));

        return Command::SUCCESS;
    }

    private function sendReminder($contributor, int $year, int $month): void
    {
        $email = new Email()
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($contributor->getEmail(), $contributor->getFullName()))
            ->subject(sprintf('Rappel : Satisfaction mensuelle - %s %d', $this->getMonthLabel($month), $year))
            ->html(sprintf(
                '<p>Bonjour %s,</p>'
                .'<p>Nous aimerions connaître votre niveau de satisfaction pour le mois de %s %d.</p>'
                .'<p>Prenez quelques minutes pour nous faire part de votre ressenti.</p>'
                .'<p><a href="%s">Saisir ma satisfaction</a></p>'
                .'<p>Merci !</p>',
                $contributor->getFullName(),
                $this->getMonthLabel($month),
                $year,
                'https://votre-domaine.com/satisfaction/submit/'.$year.'/'.$month, // TODO: Générer l'URL correcte
            ));

        $this->mailer->send($email);
    }

    private function getMonthLabel(int $month): string
    {
        return match ($month) {
            1       => 'Janvier',
            2       => 'Février',
            3       => 'Mars',
            4       => 'Avril',
            5       => 'Mai',
            6       => 'Juin',
            7       => 'Juillet',
            8       => 'Août',
            9       => 'Septembre',
            10      => 'Octobre',
            11      => 'Novembre',
            12      => 'Décembre',
            default => '',
        };
    }
}
