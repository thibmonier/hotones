<?php

namespace App\Controller;

use App\Entity\Notification;
use App\Repository\NotificationRepository;
use App\Service\NotificationService;
use DateTimeImmutable;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/notifications')]
#[IsGranted('ROLE_USER')]
class NotificationController extends AbstractController
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly NotificationRepository $notificationRepository
    ) {
    }

    /**
     * Page principale de liste des notifications.
     */
    #[Route('', name: 'notification_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $user  = $this->getUser();
        $page  = max(1, $request->query->getInt('page', 1));
        $limit = 20;

        $queryBuilder = $this->notificationRepository->createQueryBuilder('n')
            ->where('n.recipient = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->setFirstResult(($page - 1) * $limit);

        // Filtrer par type si demandé
        $type = $request->query->get('type');
        if ($type) {
            $queryBuilder->andWhere('n.type = :type')
                ->setParameter('type', $type);
        }

        // Filtrer par statut lu/non lu
        $status = $request->query->get('status');
        if ($status === 'unread') {
            $queryBuilder->andWhere('n.readAt IS NULL');
        } elseif ($status === 'read') {
            $queryBuilder->andWhere('n.readAt IS NOT NULL');
        }

        $notifications = $queryBuilder->getQuery()->getResult();

        // Compter le total pour la pagination
        $totalQuery = $this->notificationRepository->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.recipient = :user')
            ->setParameter('user', $user);

        if ($type) {
            $totalQuery->andWhere('n.type = :type')
                ->setParameter('type', $type);
        }

        if ($status === 'unread') {
            $totalQuery->andWhere('n.readAt IS NULL');
        } elseif ($status === 'read') {
            $totalQuery->andWhere('n.readAt IS NOT NULL');
        }

        $total      = (int) $totalQuery->getQuery()->getSingleScalarResult();
        $totalPages = (int) ceil($total / $limit);

        return $this->render('notification/index.html.twig', [
            'notifications' => $notifications,
            'current_page'  => $page,
            'total_pages'   => $totalPages,
            'total'         => $total,
            'type_filter'   => $type,
            'status_filter' => $status,
        ]);
    }

    /**
     * API : Récupère les notifications non lues (pour le dropdown header).
     */
    #[Route('/api/unread', name: 'notification_api_unread', methods: ['GET'])]
    public function getUnread(): JsonResponse
    {
        $user          = $this->getUser();
        $notifications = $this->notificationService->getUnreadNotifications($user, 10);
        $count         = $this->notificationService->countUnreadNotifications($user);

        $data = array_map(fn (Notification $n) => [
            'id'               => $n->getId(),
            'type'             => $n->getType()->value,
            'title'            => $n->getTitle(),
            'message'          => $n->getMessage(),
            'icon'             => $n->getType()->getIcon(),
            'color'            => $n->getType()->getColor(),
            'url'              => $n->getEntityUrl(),
            'created_at'       => $n->getCreatedAt()->format('c'),
            'created_at_human' => $this->formatRelativeTime($n->getCreatedAt()),
        ], $notifications);

        return new JsonResponse([
            'notifications' => $data,
            'count'         => $count,
        ]);
    }

    /**
     * Marquer une notification comme lue.
     */
    #[Route('/{id}/read', name: 'notification_mark_read', methods: ['POST'])]
    public function markAsRead(Notification $notification): JsonResponse
    {
        // Vérifier que c'est bien la notification de l'utilisateur
        if ($notification->getRecipient() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Forbidden'], Response::HTTP_FORBIDDEN);
        }

        $this->notificationService->markAsRead($notification);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Marquer toutes les notifications comme lues.
     */
    #[Route('/mark-all-read', name: 'notification_mark_all_read', methods: ['POST'])]
    public function markAllAsRead(): JsonResponse
    {
        $user  = $this->getUser();
        $count = $this->notificationService->markAllAsRead($user);

        return new JsonResponse([
            'success' => true,
            'count'   => $count,
        ]);
    }

    /**
     * Supprimer une notification.
     */
    #[Route('/{id}/delete', name: 'notification_delete', methods: ['POST'])]
    public function delete(Request $request, Notification $notification): Response
    {
        // Vérifier que c'est bien la notification de l'utilisateur
        if ($notification->getRecipient() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$notification->getId(), $request->request->get('_token'))) {
            $this->notificationService->deleteNotification($notification);
            $this->addFlash('success', 'Notification supprimée');
        }

        return $this->redirectToRoute('notification_index');
    }

    /**
     * Formate un temps relatif (ex: "il y a 5 minutes").
     */
    private function formatRelativeTime(DateTimeImmutable $date): string
    {
        $now  = new DateTimeImmutable();
        $diff = $now->getTimestamp() - $date->getTimestamp();

        if ($diff < 60) {
            return 'À l\'instant';
        } elseif ($diff < 3600) {
            $minutes = (int) floor($diff / 60);

            return "Il y a {$minutes} minute".($minutes > 1 ? 's' : '');
        } elseif ($diff < 86400) {
            $hours = (int) floor($diff / 3600);

            return "Il y a {$hours} heure".($hours > 1 ? 's' : '');
        } elseif ($diff < 604800) {
            $days = (int) floor($diff / 86400);

            return "Il y a {$days} jour".($days > 1 ? 's' : '');
        } else {
            return $date->format('d/m/Y à H:i');
        }
    }
}
