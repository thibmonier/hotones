<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

/**
 * Sprint-020 sub-epic D AUDIT-CONTRIBUTORS-CJM (Risk Q3 héritage audit
 * EPIC-003 sprint-019).
 *
 * Identifie les contributeurs sans CJM (ni direct sur `Contributor.cjm`, ni
 * via `EmploymentPeriod.cjm` actif) → coût 0 silencieux dans la marge
 * projet calculée Phase 3 sprint-022.
 *
 * Pré-requis bloquant US-098 EPIC-003 Phase 2 ACL deploy : doivent être
 * corrigés côté admin avant migration WorkItem aggregate en prod.
 *
 * @see docs/02-architecture/epic-003-audit-existing-data.md
 * @see ADR-0013 EPIC-003 scope WorkItem & Profitability
 */
#[AsCommand(
    name: 'app:audit:contributors-cjm',
    description: 'EPIC-003 — Audit Contributors sans CJM (Risk Q3) avant Phase 2 ACL',
    aliases: ['hotones:audit-contributors-cjm'],
)]
final class AuditContributorsCjmCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'include-inactive',
                'i',
                InputOption::VALUE_NONE,
                'Inclure les contributeurs inactifs (par défaut : actifs uniquement)',
            )
            ->addOption(
                'tjm',
                't',
                InputOption::VALUE_NONE,
                'Auditer également les TJM manquants (par défaut : CJM uniquement)',
            )
            ->addOption(
                'audit-daily-hours',
                null,
                InputOption::VALUE_NONE,
                'EPIC-003 Phase 3 (sprint-021 AUDIT-DAILY-HOURS) : auditer EmploymentPeriod.weeklyHours / workTimePercentage NULL ou aberrants (invariant journalier ADR-0015 inopérant si manquant)',
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $includeInactive = (bool) $input->getOption('include-inactive');
        $auditTjm = (bool) $input->getOption('tjm');
        $auditDailyHours = (bool) $input->getOption('audit-daily-hours');

        // AT-3.4 + AT-3.5 (ADR-0016) : defense-in-depth Postgres-level —
        // toute écriture rejetée par PG engine même si DB user a privilèges
        // CRUD. Cost ~1 ligne SQL.
        $this->enforceReadOnlyTransaction($io);

        if ($auditDailyHours) {
            return $this->executeDailyHoursAudit($io, $output, $includeInactive);
        }

        $io->title('EPIC-003 — Audit Contributors sans CJM (Risk Q3)');
        $io->note(sprintf(
            'Scope : %s · audit %s',
            $includeInactive ? 'tous contributeurs' : 'contributeurs actifs uniquement',
            $auditTjm ? 'CJM + TJM' : 'CJM uniquement',
        ));

        $contributors = $this->loadContributors($includeInactive);

        if ($contributors === []) {
            $io->warning('Aucun contributeur trouvé dans le scope demandé.');

            return Command::SUCCESS;
        }

        $io->info(sprintf('Analyse de %d contributeurs…', count($contributors)));

        $missing = [];
        $now = new DateTimeImmutable('today');
        foreach ($contributors as $contributor) {
            $resolvedCjm = $this->resolveRate($contributor, 'cjm', $now);
            $resolvedTjm = $auditTjm ? $this->resolveRate($contributor, 'tjm', $now) : 'skipped';

            $missingCjm = $resolvedCjm === null;
            $missingTjm = $auditTjm && $resolvedTjm === null;

            if (!$missingCjm && !$missingTjm) {
                continue;
            }

            $missing[] = [
                'id' => $contributor->getId(),
                'email' => $contributor->getEmail() ?? '(no email)',
                'name' => trim(($contributor->getFirstName() ?? '').' '.($contributor->getLastName() ?? '')),
                'company' => $contributor->getCompany()->getId(),
                'cjm' => $missingCjm ? '❌ NULL' : '✅ '.$resolvedCjm,
                'tjm' => $auditTjm ? ($missingTjm ? '❌ NULL' : '✅ '.$resolvedTjm) : '—',
                'has_active_period' => $this->hasActivePeriod($contributor, $now) ? 'yes' : 'no',
            ];
        }

        if ($missing === []) {
            $io->success(sprintf(
                'OK : tous les %d contributeurs ont un %s résolu (Risk Q3 OK).',
                count($contributors),
                $auditTjm ? 'CJM + TJM' : 'CJM',
            ));

            return Command::SUCCESS;
        }

        $this->renderMissingTable($output, $missing, $auditTjm);

        $io->warning(sprintf(
            '⚠️ Risk Q3 détecté : %d / %d contributeurs sans rate résolu.',
            count($missing),
            count($contributors),
        ));
        $io->writeln('Action requise avant US-098 Phase 2 ACL deploy :');
        $io->listing([
            '1. Corriger les CJM/TJM manquants côté admin (Contributor ou EmploymentPeriod)',
            '2. Re-exécuter cette commande pour valider 0 manquant',
            '3. Marquer Risk Q3 résolu dans audit doc + sprint-020 retro',
        ]);

        return Command::FAILURE;
    }

    /**
     * @return list<Contributor>
     */
    private function loadContributors(bool $includeInactive): array
    {
        $qb = $this->em->getRepository(Contributor::class)->createQueryBuilder('c');

        if (!$includeInactive) {
            $qb->andWhere('c.active = :active')->setParameter('active', true);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Résout cjm OU tjm en suivant la même logique que Contributor property
     * hook : EmploymentPeriod actif → fallback Contributor direct → null.
     */
    private function resolveRate(Contributor $contributor, string $field, DateTimeImmutable $at): ?string
    {
        $period = $this->findActiveEmploymentPeriod($contributor, $at);

        if ($period !== null) {
            $rate = $field === 'cjm' ? $period->cjm : $period->tjm;
            if ($rate !== null && trim($rate) !== '' && (float) $rate > 0) {
                return $rate;
            }
        }

        $direct = $field === 'cjm' ? $contributor->cjm : $contributor->tjm;
        if ($direct !== null && trim($direct) !== '' && (float) $direct > 0) {
            return $direct;
        }

        return null;
    }

    private function findActiveEmploymentPeriod(
        Contributor $contributor,
        DateTimeImmutable $at,
    ): ?EmploymentPeriod {
        $periods = $this->em->getRepository(EmploymentPeriod::class)->findBy([
            'contributor' => $contributor,
        ]);

        foreach ($periods as $period) {
            $start = $period->startDate ?? null;
            $end = $period->endDate ?? null;
            if ($start !== null && $start > $at) {
                continue;
            }
            if ($end !== null && $end < $at) {
                continue;
            }

            return $period;
        }

        return null;
    }

    private function hasActivePeriod(Contributor $contributor, DateTimeImmutable $at): bool
    {
        return $this->findActiveEmploymentPeriod($contributor, $at) !== null;
    }

    /**
     * @param list<array{id: int|null, email: string, name: string, company: int|string, cjm: string, tjm: string, has_active_period: string}> $missing
     */
    private function renderMissingTable(OutputInterface $output, array $missing, bool $auditTjm): void
    {
        $table = new Table($output);
        $headers = ['ID', 'Email', 'Nom', 'Company', 'CJM'];
        if ($auditTjm) {
            $headers[] = 'TJM';
        }
        $headers[] = 'Période active ?';

        $table->setHeaders($headers);

        foreach ($missing as $row) {
            $line = [$row['id'], $row['email'], $row['name'], $row['company'], $row['cjm']];
            if ($auditTjm) {
                $line[] = $row['tjm'];
            }
            $line[] = $row['has_active_period'];
            $table->addRow($line);
        }

        $table->render();
    }

    /**
     * AT-3.4 + AT-3.5 (ADR-0016) : SET TRANSACTION READ ONLY Postgres-level.
     *
     * Si DB engine n'est pas Postgres (ex SQLite tests), pattern best-effort
     * — la commande ne fait que des SELECT donc impact écriture nul.
     */
    private function enforceReadOnlyTransaction(SymfonyStyle $io): void
    {
        $connection = $this->em->getConnection();
        $platform = $connection->getDatabasePlatform()::class;

        try {
            // PostgreSQL : SET TRANSACTION READ ONLY (rejette toute écriture
            // même si user a privilèges CRUD)
            if (str_contains(strtolower($platform), 'postgres')) {
                $connection->executeStatement('SET TRANSACTION READ ONLY');
                $io->note('AT-3.5 : SET TRANSACTION READ ONLY appliqué (PostgreSQL).');

                return;
            }

            // MariaDB / MySQL : equivalent
            if (str_contains(strtolower($platform), 'mariadb') || str_contains(strtolower($platform), 'mysql')) {
                $connection->executeStatement('SET TRANSACTION READ ONLY');
                $io->note('AT-3.5 : SET TRANSACTION READ ONLY appliqué (MySQL/MariaDB).');

                return;
            }

            // SQLite (tests) : pas de SET TRANSACTION READ ONLY natif. Skip.
            $io->note(sprintf('AT-3.5 : platform %s ne supporte pas SET TRANSACTION READ ONLY — skip (commande SELECT-only intrinsèquement safe).', $platform));
        } catch (Throwable $e) {
            // Best-effort : ne pas bloquer audit si la transaction read-only échoue.
            $io->warning(sprintf('SET TRANSACTION READ ONLY non appliqué : %s', $e->getMessage()));
        }
    }

    /**
     * EPIC-003 Phase 3 (sprint-021 AUDIT-DAILY-HOURS) — audit
     * EmploymentPeriod.weeklyHours / workTimePercentage NULL ou aberrants.
     *
     * Invariant journalier (ADR-0015) :
     *   dailyMaxHours = (weeklyHours × workTimePercentage / 100) / 5
     *
     * Si weeklyHours OU workTimePercentage NULL/0/aberrants → invariant
     * journalier inopérant → DailyHoursValidator throw
     * NoActiveEmploymentPeriodException ou calculs faux.
     *
     * Bornes valides :
     * - weeklyHours : strictly > 0, max 80 (Domain VO WeeklyHours)
     * - workTimePercentage : strictly > 0, max 100 (Domain VO WorkTimePercentage)
     */
    private function executeDailyHoursAudit(
        SymfonyStyle $io,
        OutputInterface $output,
        bool $includeInactive,
    ): int {
        $io->title('EPIC-003 Phase 3 — Audit EmploymentPeriod weeklyHours / workTimePercentage');
        $io->note(sprintf(
            'Scope : %s · ADR-0015 invariant journalier',
            $includeInactive ? 'tous contributeurs' : 'contributeurs actifs uniquement',
        ));

        $contributors = $this->loadContributors($includeInactive);

        if ($contributors === []) {
            $io->warning('Aucun contributeur trouvé dans le scope demandé.');

            return Command::SUCCESS;
        }

        $io->info(sprintf('Analyse de %d contributeurs…', count($contributors)));

        $issues = [];
        $now = new DateTimeImmutable('today');

        foreach ($contributors as $contributor) {
            $period = $this->findActiveEmploymentPeriod($contributor, $now);

            if ($period === null) {
                $issues[] = [
                    'id' => $contributor->getId(),
                    'email' => $contributor->getEmail() ?? '(no email)',
                    'name' => trim(($contributor->getFirstName() ?? '').' '.($contributor->getLastName() ?? '')),
                    'weekly_hours' => '❌ aucune période active',
                    'work_time_percentage' => '—',
                    'daily_max_hours' => '—',
                    'severity' => 'CRITICAL',
                ];

                continue;
            }

            $issue = $this->checkDailyHoursValidity($contributor, $period);
            if ($issue !== null) {
                $issues[] = $issue;
            }
        }

        if ($issues === []) {
            $io->success(sprintf(
                'OK : tous les %d contributeurs ont weeklyHours + workTimePercentage valides (ADR-0015 invariant journalier opérant).',
                count($contributors),
            ));

            return Command::SUCCESS;
        }

        $this->renderDailyHoursIssuesTable($output, $issues);

        $io->warning(sprintf(
            '⚠️ %d / %d contributeurs avec EmploymentPeriod weeklyHours/workTimePercentage invalides.',
            count($issues),
            count($contributors),
        ));
        $io->writeln('Action requise avant déploiement Phase 3 prod (US-099/US-101) :');
        $io->listing([
            '1. Corriger weeklyHours/workTimePercentage côté admin (EmploymentPeriod entity)',
            '2. Re-exécuter --audit-daily-hours pour valider 0 issue',
            '3. Marquer AUDIT-DAILY-HOURS résolu sprint-021 retro',
        ]);

        return Command::FAILURE;
    }

    /**
     * @return array{id: int|null, email: string, name: string, weekly_hours: string, work_time_percentage: string, daily_max_hours: string, severity: string}|null
     */
    private function checkDailyHoursValidity(Contributor $contributor, EmploymentPeriod $period): ?array
    {
        $weeklyHoursStr = $period->weeklyHours;
        $workTimePercentageStr = $period->workTimePercentage;

        $weeklyHours = (float) $weeklyHoursStr;
        $workTimePercentage = (float) $workTimePercentageStr;

        $weeklyHoursValid = $weeklyHours > 0.0 && $weeklyHours <= 80.0;
        $percentageValid = $workTimePercentage > 0.0 && $workTimePercentage <= 100.0;

        if ($weeklyHoursValid && $percentageValid) {
            return null;
        }

        $dailyMax = $weeklyHoursValid && $percentageValid
            ? sprintf('%.2fh', ($weeklyHours * ($workTimePercentage / 100.0)) / 5.0)
            : 'invariant invalide';

        $severity = (!$weeklyHoursValid && !$percentageValid) ? 'CRITICAL' : 'WARN';

        return [
            'id' => $contributor->getId(),
            'email' => $contributor->getEmail() ?? '(no email)',
            'name' => trim($contributor->getFirstName().' '.$contributor->getLastName()),
            'weekly_hours' => $weeklyHoursValid ? '✅ '.$weeklyHoursStr : '❌ '.($weeklyHoursStr === '' ? 'vide' : $weeklyHoursStr),
            'work_time_percentage' => $percentageValid ? '✅ '.$workTimePercentageStr : '❌ '.($workTimePercentageStr === '' ? 'vide' : $workTimePercentageStr),
            'daily_max_hours' => $dailyMax,
            'severity' => $severity,
        ];
    }

    /**
     * @param list<array{id: int|null, email: string, name: string, weekly_hours: string, work_time_percentage: string, daily_max_hours: string, severity: string}> $issues
     */
    private function renderDailyHoursIssuesTable(OutputInterface $output, array $issues): void
    {
        $table = new Table($output);
        $table->setHeaders(['Sev', 'ID', 'Email', 'Nom', 'weeklyHours', 'workTimePercentage', 'dailyMaxHours']);

        foreach ($issues as $row) {
            $table->addRow([
                $row['severity'],
                $row['id'],
                $row['email'],
                $row['name'],
                $row['weekly_hours'],
                $row['work_time_percentage'],
                $row['daily_max_hours'],
            ]);
        }

        $table->render();
    }
}
