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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $includeInactive = (bool) $input->getOption('include-inactive');
        $auditTjm = (bool) $input->getOption('tjm');

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
                'company' => $contributor->getCompany()?->getId() ?? '(no company)',
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
            if ($rate !== null && trim((string) $rate) !== '' && (float) $rate > 0) {
                return (string) $rate;
            }
        }

        $direct = $field === 'cjm' ? $contributor->cjm : $contributor->tjm;
        if ($direct !== null && trim((string) $direct) !== '' && (float) $direct > 0) {
            return (string) $direct;
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
}
