<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Notification;
use App\Entity\Timesheet;
use App\Enum\NotificationType;
use DateInterval;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:notify:timesheets-weekly',
    description: 'Notifie les contributeurs n’ayant pas saisi suffisamment d’heures sur la semaine en cours (tolérance 15%)',
)]
final class NotifyWeeklyTimesheetsCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly \App\Repository\NotificationSettingRepository $settings,
        private readonly \App\Repository\NotificationPreferenceRepository $prefs,
        private readonly \Symfony\Component\Mailer\MailerInterface $mailer,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $now        = new DateTimeImmutable('now');
        $monday     = $this->getStartOfWeek($now);
        $fridayNoon = $this->getFridayNoon($now);

        $contribRepo   = $this->em->getRepository(Contributor::class);
        $timesheetRepo = $this->em->getRepository(Timesheet::class);
        $contributors  = $contribRepo->findBy(['active' => true]);

        $notified = 0;
        foreach ($contributors as $contrib) {
            $user = $contrib->getUser();
            if (!$user) {
                continue; // pas de destinataire in-app
            }

            $period = $this->getActiveEmploymentPeriod($contrib, $now);
            if (!$period) {
                continue;
            }

            $expected = $this->getExpectedWeeklyHours($period);
            // Tolerance configurable (default 0.15)
            $tolerance = (float) $this->settings->getValue(\App\Repository\NotificationSettingRepository::KEY_TIMESHEET_WEEKLY_TOLERANCE, 0.15);
            if ($expected <= 0.0) {
                continue;
            }

            // Somme des heures sur la semaine
            $logged = $this->sumTimesheetHours($contrib, $monday, $fridayNoon);

            // Tolérance configurable (par défaut 15%)
            $threshold = $expected * (1.0 - $tolerance);
            if ($logged + 0.0001 < $threshold) {
                $notif = new Notification();
                $notif->setRecipient($user)
                    ->setType(NotificationType::TIMESHEET_MISSING_WEEKLY)
                    ->setTitle('Rappel de saisie des temps')
                    ->setMessage(sprintf(
                        'Vous avez saisi %.2f h sur %.2f h attendues cette semaine (tolérance 15%%). Merci de compléter vos temps.',
                        $logged,
                        $expected,
                    ))
                    ->setData([
                        'start'          => $monday->format('Y-m-d'),
                        'end'            => $fridayNoon->format('Y-m-d'),
                        'expected_hours' => round($expected, 2),
                        'logged_hours'   => round($logged, 2),
                        'url'            => '/timesheet',
                    ]);

                $this->em->persist($notif);
                ++$notified;

                // Email si autorisé par préférences (par défaut true si aucune préférence)
                $pref      = $this->prefs->findByUserAndEventType($user, NotificationType::TIMESHEET_MISSING_WEEKLY);
                $sendEmail = $pref ? $pref->isEmail() : true;
                if ($sendEmail && $user->getEmail()) {
                    $email = new \Symfony\Component\Mime\Email()
                        ->to($user->getEmail())
                        ->subject('Rappel – Saisie des temps (hebdomadaire)')
                        ->text(sprintf(
                            "Bonjour %s,\n\nVous avez saisi %.2f h sur %.2f h attendues cette semaine (tolérance %d%%). Merci de compléter vos temps avant la fin de semaine.\n\nPériode: %s → %s\n",
                            $user->getFirstName(),
                            $logged,
                            $expected,
                            (int) round($tolerance * 100),
                            $monday->format('Y-m-d'),
                            $fridayNoon->format('Y-m-d'),
                        ));
                    $this->mailer->send($email);
                }
            }
        }

        if ($notified > 0) {
            $this->em->flush();
        }

        $output->writeln(sprintf('Notifications créées: %d', $notified));

        return Command::SUCCESS;
    }

    private function getStartOfWeek(DateTimeImmutable $date): DateTimeImmutable
    {
        // Lundi 00:00
        $dow   = (int) $date->format('N'); // 1 (Mon) - 7 (Sun)
        $delta = $dow - 1;

        return $date->setTime(0, 0)->sub(new DateInterval('P'.$delta.'D'));
    }

    private function getFridayNoon(DateTimeImmutable $date): DateTimeImmutable
    {
        // Vendredi 12:00:00 de la semaine courante
        $dow           = (int) $date->format('N'); // 1..7
        $deltaToFriday = 5 - $dow;
        // Si on est déjà après vendredi midi, rester sur vendredi de cette semaine
        if ($deltaToFriday < 0) {
            return $date->setTime(12, 0, 0)->sub(new DateInterval('P'.abs($deltaToFriday).'D'));
        }

        return $date->setTime(12, 0, 0)->add(new DateInterval('P'.max(0, $deltaToFriday).'D'));
    }

    private function getActiveEmploymentPeriod(Contributor $c, DateTimeInterface $at): ?EmploymentPeriod
    {
        foreach ($c->getEmploymentPeriods() as $p) {
            if ($p->isActiveAt($at)) {
                return $p;
            }
        }

        return null;
    }

    private function getExpectedWeeklyHours(EmploymentPeriod $p): float
    {
        $weekly = (float) $p->getWeeklyHours();
        $ratio  = (float) $p->getWorkTimePercentage() / 100.0;

        return $weekly * $ratio;
    }

    private function sumTimesheetHours(Contributor $c, DateTimeInterface $start, DateTimeInterface $end): float
    {
        $qb = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(ts.hours),0) as total')
            ->from(Timesheet::class, 'ts')
            ->where('ts.contributor = :c')
            ->andWhere('ts.date BETWEEN :start AND :end')
            ->setParameter('c', $c)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        $result = $qb->getQuery()->getSingleScalarResult();

        return (float) $result;
    }
}
