<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Contributor;
use App\Entity\PerformanceReview;
use App\Entity\User;
use App\Repository\ContributorRepository;
use App\Repository\PerformanceReviewRepository;
use App\Security\CompanyContext;
use App\Service\PerformanceReviewService;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

class PerformanceReviewServiceTest extends TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject $em;
    private \PHPUnit\Framework\MockObject\MockObject $reviewRepository;
    private \PHPUnit\Framework\MockObject\MockObject $contributorRepository;
    private \PHPUnit\Framework\MockObject\MockObject $companyContext;
    private \PHPUnit\Framework\MockObject\MockObject $mailer;
    private PerformanceReviewService $service;

    protected function setUp(): void
    {
        $this->em                    = $this->createMock(EntityManagerInterface::class);
        $this->reviewRepository      = $this->createMock(PerformanceReviewRepository::class);
        $this->contributorRepository = $this->createMock(ContributorRepository::class);
        $this->companyContext        = $this->createMock(CompanyContext::class);
        $this->mailer                = $this->createMock(MailerInterface::class);
        $this->service               = new PerformanceReviewService(
            $this->em,
            $this->reviewRepository,
            $this->contributorRepository,
            $this->companyContext,
            $this->mailer,
        );
    }

    public function testCreateCampaignWithoutManagers(): void
    {
        $user1 = $this->createMockUser('john@example.com');
        $user2 = $this->createMockUser('jane@example.com');

        $contributors = [
            $this->createContributor(1, 'John', 'Doe', $user1),
            $this->createContributor(2, 'Jane', 'Smith', $user2),
        ];

        $this->contributorRepository
            ->expects($this->once())
            ->method('findActiveContributors')
            ->willReturn($contributors);

        $this->reviewRepository
            ->expects($this->exactly(2))
            ->method('existsForContributorAndYear')
            ->willReturn(false);

        $this->em->expects($this->exactly(2))->method('persist');

        $this->em->expects($this->once())->method('flush');

        $count = $this->service->createCampaign(2024);

        $this->assertEquals(2, $count);
    }

    public function testCreateCampaignSkipsExistingReviews(): void
    {
        $user1 = $this->createMockUser('john@example.com');
        $user2 = $this->createMockUser('jane@example.com');

        $contributors = [
            $this->createContributor(1, 'John', 'Doe', $user1),
            $this->createContributor(2, 'Jane', 'Smith', $user2),
        ];

        $this->contributorRepository
            ->expects($this->once())
            ->method('findActiveContributors')
            ->willReturn($contributors);

        $this->reviewRepository
            ->expects($this->exactly(2))
            ->method('existsForContributorAndYear')
            ->willReturnOnConsecutiveCalls(true, false); // First exists, second doesn't

        $this->em->expects($this->once())->method('persist'); // Only persist one

        $this->em->expects($this->once())->method('flush');

        $count = $this->service->createCampaign(2024);

        $this->assertEquals(1, $count); // Only one created
    }

    public function testCompleteSelfEvaluation(): void
    {
        $review = new PerformanceReview();
        $review->setStatus('en_attente');
        $review->setYear(2024);

        $contributor = $this->createContributor(1, 'John', 'Doe');
        $manager     = $this->createMockUser('manager@example.com');
        $review->setContributor($contributor);
        $review->setManager($manager);

        $this->em->expects($this->once())->method('flush');

        $this->mailer->expects($this->once())->method('send');

        $this->service->completeSelfEvaluation(
            $review,
            'Achievement 1, Achievement 2',
            'Strong communication',
            'Technical skills',
        );

        $this->assertEquals('auto_eval_faite', $review->getStatus());
        $selfEval = $review->getSelfEvaluation();
        $this->assertEquals('Achievement 1, Achievement 2', $selfEval['achievements']);
        $this->assertEquals('Strong communication', $selfEval['strengths']);
        $this->assertEquals('Technical skills', $selfEval['improvements']);
        $this->assertArrayHasKey('completed_at', $selfEval);
    }

    public function testCompleteManagerEvaluation(): void
    {
        $manager = $this->createMockUser('manager@example.com');
        $user    = $this->createMockUser('john@example.com');

        $review = new PerformanceReview();
        $review->setStatus('auto_eval_faite');
        $review->setYear(2024);
        $review->setManager($manager);

        $contributor = $this->createContributor(1, 'John', 'Doe', $user);

        $review->setContributor($contributor);

        $this->em->expects($this->once())->method('flush');

        $this->mailer->expects($this->once())->method('send');

        $this->service->completeManagerEvaluation(
            $review,
            'Excellent work on project X',
            'Leadership, Technical expertise',
            'Time management',
            'Overall great performance',
            4,
        );

        $this->assertEquals('eval_manager_faite', $review->getStatus());
        $this->assertEquals(4, $review->getOverallRating());

        $managerEval = $review->getManagerEvaluation();
        $this->assertEquals('Excellent work on project X', $managerEval['achievements']);
        $this->assertEquals('Leadership, Technical expertise', $managerEval['strengths']);
        $this->assertEquals('Time management', $managerEval['improvements']);
        $this->assertEquals('Overall great performance', $managerEval['feedback']);
    }

    public function testValidateReview(): void
    {
        $manager = $this->createMockUser('manager@example.com');
        $user    = $this->createMockUser('john@example.com');

        $review = new PerformanceReview();
        $review->setStatus('eval_manager_faite');
        $review->setYear(2024);
        $review->setManager($manager);

        $contributor = $this->createContributor(1, 'John', 'Doe', $user);
        $review->setContributor($contributor);

        $objectives = [
            [
                'title'       => 'Improve technical skills',
                'description' => 'Complete certification',
                'deadline'    => '2025-06-30',
            ],
        ];

        $interviewDate = new DateTimeImmutable('2024-12-15');

        $this->em->expects($this->once())->method('flush');

        $this->mailer->expects($this->once())->method('send');

        $this->service->validateReview($review, $objectives, $interviewDate, 'Great discussion');

        $this->assertEquals('validee', $review->getStatus());
        $this->assertEquals($objectives, $review->getObjectives());
        $this->assertEquals($interviewDate, $review->getInterviewDate());
        $this->assertEquals('Great discussion', $review->getComments());
    }

    public function testCanEditSelfEvaluation(): void
    {
        $manager     = $this->createMockUser('manager@example.com');
        $user        = $this->createMockUser('john@example.com');
        $contributor = $this->createContributor(1, 'John', 'Doe', $user);

        $review = new PerformanceReview();
        $review->setYear(2024);
        $review->setContributor($contributor);
        $review->setManager($manager);
        $review->setStatus('en_attente');

        $this->assertTrue($this->service->canEditSelfEvaluation($review, $user));
    }

    public function testCanEditSelfEvaluationWhenAlreadyCompleted(): void
    {
        $manager     = $this->createMockUser('manager@example.com');
        $user        = $this->createMockUser('john@example.com');
        $contributor = $this->createContributor(1, 'John', 'Doe', $user);

        $review = new PerformanceReview();
        $review->setYear(2024);
        $review->setContributor($contributor);
        $review->setManager($manager);
        $review->setStatus('auto_eval_faite');

        $this->assertTrue($this->service->canEditSelfEvaluation($review, $user));
    }

    public function testCannotEditSelfEvaluationWhenManagerEvaluationCompleted(): void
    {
        $user        = $this->createMockUser('john@example.com');
        $contributor = $this->createContributor(1, 'John', 'Doe');
        $contributor->setUser($user);

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setStatus('eval_manager_faite');

        $this->assertFalse($this->service->canEditSelfEvaluation($review, $user));
    }

    public function testCanEditManagerEvaluation(): void
    {
        $manager     = $this->createMockUser('manager@example.com');
        $contributor = $this->createContributor(1, 'John', 'Doe');

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setManager($manager);
        $review->setStatus('auto_eval_faite');

        $this->assertTrue($this->service->canEditManagerEvaluation($review, $manager));
    }

    public function testCannotEditManagerEvaluationWhenSelfEvaluationNotCompleted(): void
    {
        $manager     = $this->createMockUser('manager@example.com');
        $contributor = $this->createContributor(1, 'John', 'Doe');

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setManager($manager);
        $review->setStatus('en_attente');

        $this->assertFalse($this->service->canEditManagerEvaluation($review, $manager));
    }

    public function testCanValidateReview(): void
    {
        $manager     = $this->createMockUser('manager@example.com');
        $contributor = $this->createContributor(1, 'John', 'Doe');

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setManager($manager);
        $review->setStatus('eval_manager_faite');

        $this->assertTrue($this->service->canValidateReview($review, $manager));
    }

    public function testCannotValidateReviewWhenManagerEvaluationNotCompleted(): void
    {
        $manager     = $this->createMockUser('manager@example.com');
        $contributor = $this->createContributor(1, 'John', 'Doe');

        $review = new PerformanceReview();
        $review->setContributor($contributor);
        $review->setManager($manager);
        $review->setStatus('auto_eval_faite');

        $this->assertFalse($this->service->canValidateReview($review, $manager));
    }

    public function testGetContributorHistory(): void
    {
        $contributor = $this->createContributor(1, 'John', 'Doe');
        $reviews     = [
            new PerformanceReview(),
            new PerformanceReview(),
        ];

        $this->reviewRepository
            ->expects($this->once())
            ->method('findByContributor')
            ->with($contributor)
            ->willReturn($reviews);

        $result = $this->service->getContributorHistory($contributor);

        $this->assertSame($reviews, $result);
        $this->assertCount(2, $result);
    }

    private function createContributor(int $id, string $firstName, string $lastName, ?User $user = null): Contributor
    {
        $contributor = $this->createMock(Contributor::class);
        $contributor->method('getId')->willReturn($id);
        $contributor->method('getFirstName')->willReturn($firstName);
        $contributor->method('getLastName')->willReturn($lastName);
        $contributor->method('getFullName')->willReturn($firstName.' '.$lastName);
        $contributor->method('getUser')->willReturn($user);

        return $contributor;
    }

    private function createMockUser(string $email): User
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn($email);

        return $user;
    }
}
