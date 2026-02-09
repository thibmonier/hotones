<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Entity\EmploymentPeriod;
use App\Service\OnboardingService;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Exception;
use Psr\Log\LoggerInterface;

#[AsEntityListener(event: Events::postPersist, entity: EmploymentPeriod::class)]
class EmploymentPeriodCreatedListener
{
    public function __construct(
        private readonly OnboardingService $onboardingService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Automatically create onboarding tasks when a new employment period is created.
     */
    public function postPersist(EmploymentPeriod $employmentPeriod, PostPersistEventArgs $args): void
    {
        $contributor = $employmentPeriod->getContributor();

        try {
            $tasksCreated = $this->onboardingService->createOnboardingFromTemplate($contributor, $employmentPeriod);

            if ($tasksCreated > 0) {
                $this->logger->info('Onboarding tasks created automatically', [
                    'contributor_id'       => $contributor->getId(),
                    'contributor_name'     => $contributor->getFullName(),
                    'employment_period_id' => $employmentPeriod->getId(),
                    'tasks_created'        => $tasksCreated,
                ]);
            } else {
                $profiles     = $contributor->getProfiles();
                $profileNames = $profiles->isEmpty() ? 'none' : $profiles->first()->getName();

                $this->logger->warning('No onboarding template found for contributor', [
                    'contributor_id'   => $contributor->getId(),
                    'contributor_name' => $contributor->getFullName(),
                    'profile'          => $profileNames,
                ]);
            }
        } catch (Exception $e) {
            $this->logger->error('Failed to create onboarding tasks automatically', [
                'contributor_id'       => $contributor->getId(),
                'employment_period_id' => $employmentPeriod->id, // PHP 8.4 property hook
                'error'                => $e->getMessage(),
            ]);
        }
    }
}
