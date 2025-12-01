<?php

namespace App\Controller\Api;

use App\Entity\Contributor;
use App\Repository\ProjectTaskRepository;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/tasks/api')]
#[IsGranted('ROLE_USER')]
class TaskApiController extends AbstractController
{
    public function __construct(private readonly \Doctrine\ORM\EntityManagerInterface $em)
    {
    }

    #[Route('/overdue-count', name: 'tasks_api_overdue_count', methods: ['GET'])]
    public function overdueCount(ProjectTaskRepository $repo): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['count' => 0]);
        }
        $contributor = $this->em->getRepository(Contributor::class)->findOneBy(['user' => $user]);
        if (!$contributor) {
            return new JsonResponse(['count' => 0, 'no_contributor' => true]);
        }

        $qb = $repo->createQueryBuilder('t')
            ->select('COUNT(t.id)')
            ->where('t.assignedContributor = :c')
            ->andWhere('t.active = true')
            ->andWhere('t.status != :completed')
            ->andWhere('t.endDate IS NOT NULL AND t.endDate < :today')
            ->setParameter('c', $contributor)
            ->setParameter('completed', 'completed')
            ->setParameter('today', new DateTimeImmutable('today'));

        $count = (int) $qb->getQuery()->getSingleScalarResult();

        return new JsonResponse(['count' => $count]);
    }

    #[Route('/overdue-list', name: 'tasks_api_overdue_list', methods: ['GET'])]
    public function overdueList(ProjectTaskRepository $repo): JsonResponse
    {
        $limit = max(1, (int) ($_GET['limit'] ?? 5));
        $user  = $this->getUser();
        if (!$user) {
            return new JsonResponse(['tasks' => []]);
        }
        $contributor = $this->em->getRepository(Contributor::class)->findOneBy(['user' => $user]);
        if (!$contributor) {
            return new JsonResponse(['tasks' => [], 'no_contributor' => true]);
        }

        $qb = $repo->createQueryBuilder('t')
            ->innerJoin('t.project', 'p')
            ->addSelect('p')
            ->where('t.assignedContributor = :c')
            ->andWhere('t.active = true')
            ->andWhere('t.status != :completed')
            ->andWhere('t.endDate IS NOT NULL AND t.endDate < :today')
            ->orderBy('t.endDate', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('c', $contributor)
            ->setParameter('completed', 'completed')
            ->setParameter('today', new DateTimeImmutable('today'));

        $rows = $qb->getQuery()->getResult();
        $data = [];
        foreach ($rows as $task) {
            $data[] = [
                'id'         => $task->getId(),
                'name'       => $task->getName(),
                'project_id' => $task->getProject()->getId(),
                'project'    => $task->getProject()->getName(),
                'end_date'   => $task->getEndDate() ? $task->getEndDate()->format('Y-m-d') : null,
                'url'        => sprintf('/project/%d/tasks/%d', $task->getProject()->getId(), $task->getId()),
            ];
        }

        return new JsonResponse(['tasks' => $data]);
    }
}
