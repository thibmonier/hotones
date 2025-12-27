<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Repository\ContributorRepository;
use App\Repository\CookieConsentRepository;
use App\Repository\EmploymentPeriodRepository;
use App\Repository\TimesheetRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de gestion de l'export complet des données RGPD.
 * Collecte toutes les données personnelles d'un utilisateur pour export (droit d'accès + portabilité).
 */
class GdprDataExportService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContributorRepository $contributorRepository,
        private readonly EmploymentPeriodRepository $employmentPeriodRepository,
        private readonly TimesheetRepository $timesheetRepository,
        private readonly CookieConsentRepository $cookieConsentRepository,
    ) {
    }

    /**
     * Exporte toutes les données personnelles d'un utilisateur au format JSON.
     * Conforme aux exigences RGPD pour le droit d'accès et à la portabilité.
     */
    public function exportUserData(User $user): array
    {
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        $data = [
            'export_metadata' => [
                'export_date'    => (new DateTimeImmutable())->format('c'),
                'format_version' => '1.0',
                'gdpr_rights'    => ['access', 'portability'],
            ],

            // Données d'identification
            'user_account' => $this->exportUserAccount($user),

            // Données professionnelles
            'contributor_profile' => $contributor ? $this->exportContributorProfile($contributor) : null,
            'employment_periods'  => $contributor ? $this->exportEmploymentPeriods($contributor) : [],

            // Données d'activité
            'timesheets' => $contributor ? $this->exportTimesheets($contributor) : [],

            // Données de consentement
            'cookie_consents' => $this->exportCookieConsents($user),

            // Statistiques générales
            'statistics' => $this->exportStatistics($user, $contributor),
        ];

        return $data;
    }

    private function exportUserAccount(User $user): array
    {
        return [
            'user_id'            => $user->getId(),
            'email'              => $user->getEmail(),
            'first_name'         => $user->getFirstName(),
            'last_name'          => $user->getLastName(),
            'full_name'          => $user->getFullName(),
            'roles'              => $user->getRoles(),
            'totp_enabled'       => $user->isTotpAuthenticationEnabled(),
            'account_created_at' => $user->getCreatedAt()?->format('c'),
            'last_login_at'      => $user->getLastLoginAt()?->format('c'),
            'last_login_ip'      => $user->getLastLoginIp(),
        ];
    }

    private function exportContributorProfile($contributor): array
    {
        return [
            'contributor_id'   => $contributor->getId(),
            'cjm'              => $contributor->getCjm(),
            'recruitment_date' => $contributor->getRecruitmentDate()?->format('Y-m-d'),
            'departure_date'   => $contributor->getDepartureDate()?->format('Y-m-d'),
            'is_active'        => $contributor->isActive(),
            'profiles'         => array_map(
                fn ($profile) => [
                    'id'   => $profile->getId(),
                    'name' => $profile->getName(),
                ],
                $contributor->getProfiles()->toArray(),
            ),
        ];
    }

    private function exportEmploymentPeriods($contributor): array
    {
        $periods = $this->employmentPeriodRepository->findBy(
            ['contributor' => $contributor],
            ['startDate' => 'ASC'],
        );

        return array_map(function ($period) {
            return [
                'period_id'            => $period->getId(),
                'start_date'           => $period->getStartDate()?->format('Y-m-d'),
                'end_date'             => $period->getEndDate()?->format('Y-m-d'),
                'salary'               => $period->getSalary(),
                'weekly_hours'         => $period->getWeeklyHours(),
                'work_time_percentage' => $period->getWorkTimePercentage(),
                'profiles'             => array_map(
                    fn ($profile) => $profile->getName(),
                    $period->getProfiles()->toArray(),
                ),
            ];
        }, $periods);
    }

    private function exportTimesheets($contributor): array
    {
        $timesheets = $this->timesheetRepository->findBy(
            ['contributor' => $contributor],
            ['date' => 'DESC'],
        );

        return array_map(function ($timesheet) {
            return [
                'timesheet_id' => $timesheet->getId(),
                'date'         => $timesheet->getDate()?->format('Y-m-d'),
                'hours'        => $timesheet->getHours(),
                'notes'        => $timesheet->getNotes(),
                'project'      => $timesheet->getProject() ? [
                    'id'   => $timesheet->getProject()->getId(),
                    'name' => $timesheet->getProject()->getName(),
                ] : null,
                'task' => $timesheet->getTask() ? [
                    'id'   => $timesheet->getTask()->getId(),
                    'name' => $timesheet->getTask()->getName(),
                ] : null,
                'sub_task' => $timesheet->getSubTask() ? [
                    'id'    => $timesheet->getSubTask()->getId(),
                    'title' => $timesheet->getSubTask()->getTitle(),
                ] : null,
            ];
        }, $timesheets);
    }

    private function exportCookieConsents(User $user): array
    {
        // Get all consents for this user (including expired ones for full history)
        $qb       = $this->em->createQueryBuilder();
        $consents = $qb->select('c')
            ->from('App\Entity\CookieConsent', 'c')
            ->where('c.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(function ($consent) {
            return [
                'consent_id' => $consent->getId(),
                'essential'  => $consent->isEssential(),
                'functional' => $consent->isFunctional(),
                'analytics'  => $consent->isAnalytics(),
                'version'    => $consent->getVersion(),
                'ip_address' => $consent->getIpAddress(),
                'user_agent' => $consent->getUserAgent(),
                'created_at' => $consent->getCreatedAt()?->format('c'),
                'expires_at' => $consent->getExpiresAt()?->format('c'),
                'is_expired' => $consent->isExpired(),
            ];
        }, $consents);
    }

    private function exportStatistics(User $user, $contributor): array
    {
        $stats = [
            'account_age_days' => $user->getCreatedAt()
                ? (new DateTimeImmutable())->diff($user->getCreatedAt())->days
                : null,
        ];

        if ($contributor) {
            $stats['total_timesheets']         = $this->timesheetRepository->count(['contributor' => $contributor]);
            $stats['total_hours_logged']       = $this->calculateTotalHours($contributor);
            $stats['total_employment_periods'] = $this->employmentPeriodRepository->count(['contributor' => $contributor]);
        }

        return $stats;
    }

    private function calculateTotalHours($contributor): string
    {
        $qb     = $this->em->createQueryBuilder();
        $result = $qb->select('SUM(t.hours) as total')
            ->from('App\Entity\Timesheet', 't')
            ->where('t.contributor = :contributor')
            ->setParameter('contributor', $contributor)
            ->getQuery()
            ->getSingleScalarResult();

        return (string) ($result ?? '0');
    }
}
