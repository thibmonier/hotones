<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Contributor;
use App\Entity\PerformanceReview;
use App\Entity\User;
use App\Repository\ContributorRepository;
use App\Repository\PerformanceReviewRepository;
use App\Security\CompanyContext;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class PerformanceReviewService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PerformanceReviewRepository $reviewRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly CompanyContext $companyContext,
        private readonly MailerInterface $mailer,
    ) {
    }

    /**
     * Create a performance review campaign for a given year.
     *
     * @param User[] $managers Array of managers to create reviews for their teams
     *
     * @return int Number of reviews created
     */
    public function createCampaign(int $year, array $managers = []): int
    {
        $created = 0;

        // If no managers specified, get all active contributors
        if (empty($managers)) {
            $contributors = $this->contributorRepository->findActiveContributors();
        } else {
            $contributors = [];
            foreach ($managers as $manager) {
                // Get team members for each manager
                $teamMembers  = $this->getTeamMembers($manager);
                $contributors = array_merge($contributors, $teamMembers);
            }
            $contributors = array_unique($contributors, SORT_REGULAR);
        }

        foreach ($contributors as $contributor) {
            // Check if review already exists for this contributor and year
            if ($this->reviewRepository->existsForContributorAndYear($contributor, $year)) {
                continue;
            }

            // Get contributor's manager
            $manager = $this->getContributorManager($contributor);
            if (null === $manager) {
                continue; // Skip if no manager found
            }

            $review = new PerformanceReview();
            $review->setCompany($this->companyContext->getCurrentCompany());
            $review->setYear($year);
            $review->setContributor($contributor);
            $review->setManager($manager);
            $review->setStatus('en_attente');

            $this->em->persist($review);
            ++$created;
        }

        $this->em->flush();

        return $created;
    }

    /**
     * Send notification email based on review step.
     */
    public function sendNotifications(PerformanceReview $review, string $step): void
    {
        $contributor = $review->getContributor();
        $manager     = $review->getManager();
        $year        = $review->getYear();

        switch ($step) {
            case 'self_evaluation_request':
                $this->sendEmail(
                    $contributor->getUser()?->getEmail(),
                    "Évaluation annuelle {$year} - Auto-évaluation à compléter",
                    "Bonjour {$contributor->getFirstName()},\n\nVotre auto-évaluation pour l'année {$year} est disponible.\nMerci de la compléter dans les plus brefs délais.",
                );
                break;

            case 'self_evaluation_completed':
                $this->sendEmail(
                    $manager->getEmail(),
                    "Évaluation annuelle {$year} - Auto-évaluation complétée par {$contributor->getFullName()}",
                    "Bonjour,\n\n{$contributor->getFullName()} a complété son auto-évaluation pour {$year}.\nVous pouvez maintenant compléter votre évaluation manager.",
                );
                break;

            case 'manager_evaluation_completed':
                $this->sendEmail(
                    $contributor->getUser()?->getEmail(),
                    "Évaluation annuelle {$year} - Évaluation manager complétée",
                    "Bonjour {$contributor->getFirstName()},\n\nVotre manager a complété l'évaluation pour {$year}.\nUn entretien sera planifié prochainement.",
                );
                break;

            case 'review_validated':
                $this->sendEmail(
                    $contributor->getUser()?->getEmail(),
                    "Évaluation annuelle {$year} - Évaluation validée",
                    "Bonjour {$contributor->getFirstName()},\n\nVotre évaluation annuelle {$year} a été validée.\nVous pouvez la consulter à tout moment dans votre espace.",
                );
                break;
        }
    }

    /**
     * Complete self-evaluation.
     */
    public function completeSelfEvaluation(
        PerformanceReview $review,
        string $achievements,
        string $strengths,
        string $improvements
    ): void {
        $review->setSelfEvaluation([
            'achievements' => $achievements,
            'strengths'    => $strengths,
            'improvements' => $improvements,
            'completed_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ]);

        $review->setStatus('auto_eval_faite');

        $this->em->flush();

        // Send notification to manager
        $this->sendNotifications($review, 'self_evaluation_completed');
    }

    /**
     * Complete manager evaluation.
     */
    public function completeManagerEvaluation(
        PerformanceReview $review,
        string $achievements,
        string $strengths,
        string $improvements,
        string $feedback,
        ?int $rating = null
    ): void {
        $review->setManagerEvaluation([
            'achievements' => $achievements,
            'strengths'    => $strengths,
            'improvements' => $improvements,
            'feedback'     => $feedback,
            'completed_at' => new DateTimeImmutable()->format('Y-m-d H:i:s'),
        ]);

        if (null !== $rating) {
            $review->setOverallRating($rating);
        }

        $review->setStatus('eval_manager_faite');

        $this->em->flush();

        // Send notification to contributor
        $this->sendNotifications($review, 'manager_evaluation_completed');
    }

    /**
     * Validate review and set objectives for next year.
     *
     * @param array $objectives Array of SMART objectives
     */
    public function validateReview(
        PerformanceReview $review,
        array $objectives,
        ?DateTimeImmutable $interviewDate = null,
        ?string $comments = null
    ): void {
        $review->setObjectives($objectives);

        if (null !== $interviewDate) {
            $review->setInterviewDate($interviewDate);
        }

        if (null !== $comments) {
            $review->setComments($comments);
        }

        $review->validate();

        $this->em->flush();

        // Send notification to contributor
        $this->sendNotifications($review, 'review_validated');
    }

    /**
     * Get team members for a manager.
     *
     * @return Contributor[]
     */
    private function getTeamMembers(User $manager): array
    {
        // This is a simplified version - you may need to implement
        // a proper team hierarchy based on your business logic
        return $this->contributorRepository->findBy(['user' => $manager]);
    }

    /**
     * Get contributor's manager.
     */
    private function getContributorManager(Contributor $contributor): ?User
    {
        // This is a simplified version - implement based on your hierarchy
        // For now, return a default admin user or null
        $user = $contributor->getUser();

        // You could implement a hierarchy field on Contributor or User entity
        // For example: $contributor->getManager() or $user->getManager()

        return $user; // Placeholder - adjust based on your actual hierarchy
    }

    /**
     * Send email helper.
     */
    private function sendEmail(?string $to, string $subject, string $body): void
    {
        if (null === $to) {
            return;
        }

        $email = new Email()
            ->from('noreply@hotones.app')
            ->to($to)
            ->subject($subject)
            ->text($body);

        try {
            $this->mailer->send($email);
        } catch (Exception) {
            // Log error but don't fail the operation
            // You could inject a logger here
        }
    }

    /**
     * Get statistics for performance reviews.
     *
     * @return array{years: int[], stats_by_year: array}
     */
    public function getStatistics(): array
    {
        // Get distinct years
        $years = $this->em->createQuery(
            'SELECT DISTINCT pr.year FROM App\Entity\PerformanceReview pr ORDER BY pr.year DESC',
        )->getResult();

        $years = array_column($years, 'year');

        $statsByYear = [];
        foreach ($years as $year) {
            $statsByYear[$year] = $this->reviewRepository->getStatsByYear($year);
        }

        return [
            'years'         => $years,
            'stats_by_year' => $statsByYear,
        ];
    }

    /**
     * Get reviews history for a contributor.
     *
     * @return PerformanceReview[]
     */
    public function getContributorHistory(Contributor $contributor): array
    {
        return $this->reviewRepository->findByContributor($contributor);
    }

    /**
     * Check if contributor can edit self-evaluation.
     */
    public function canEditSelfEvaluation(PerformanceReview $review, User $user): bool
    {
        // User must be the contributor and status must be en_attente or auto_eval_faite
        return $review->getContributor()->getUser() === $user
            && in_array($review->getStatus(), ['en_attente', 'auto_eval_faite'], true);
    }

    /**
     * Check if manager can edit manager evaluation.
     */
    public function canEditManagerEvaluation(PerformanceReview $review, User $user): bool
    {
        // User must be the manager and self-evaluation must be completed
        return $review->getManager() === $user
            && in_array($review->getStatus(), ['auto_eval_faite', 'eval_manager_faite'], true);
    }

    /**
     * Check if manager can validate review.
     */
    public function canValidateReview(PerformanceReview $review, User $user): bool
    {
        // User must be the manager and both evaluations must be completed
        return $review->getManager() === $user
            && 'eval_manager_faite'  === $review->getStatus();
    }
}
