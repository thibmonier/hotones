<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Badge;
use App\Repository\BadgeRepository;
use App\Security\CompanyContext;
use Doctrine\ORM\EntityManagerInterface;
use JsonException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/badges')]
#[IsGranted('ROLE_ADMIN')]
class BadgeController extends AbstractController
{
    public function __construct(
        private readonly BadgeRepository $badgeRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CompanyContext $companyContext
    ) {
    }

    #[Route('', name: 'badge_index', methods: ['GET'])]
    public function index(): Response
    {
        $badges = $this->badgeRepository->findBy([], ['category' => 'ASC', 'xpReward' => 'ASC']);
        $stats  = $this->badgeRepository->getBadgeStats();

        return $this->render('badge/index.html.twig', [
            'badges' => $badges,
            'stats'  => $stats,
        ]);
    }

    #[Route('/new', name: 'badge_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            $badge = new Badge();
            $badge->setCompany($this->companyContext->getCurrentCompany());
            $badge->setName($request->request->get('name'));
            $badge->setDescription($request->request->get('description'));
            $badge->setIcon($request->request->get('icon'));
            $badge->setCategory($request->request->get('category'));
            $badge->setXpReward((int) $request->request->get('xp_reward'));

            // Parse criteria JSON
            $criteriaJson = $request->request->get('criteria');
            if ($criteriaJson) {
                try {
                    $criteria = json_decode($criteriaJson, true, 512, JSON_THROW_ON_ERROR);
                    $badge->setCriteria($criteria);
                } catch (JsonException) {
                    $this->addFlash('error', 'Format JSON invalide pour les critères');

                    return $this->render('badge/new.html.twig', [
                        'badge'      => $badge,
                        'categories' => $this->getBadgeCategories(),
                    ]);
                }
            }

            $badge->setActive(true);

            $this->entityManager->persist($badge);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le badge a été créé avec succès');

            return $this->redirectToRoute('badge_index');
        }

        return $this->render('badge/new.html.twig', [
            'categories' => $this->getBadgeCategories(),
        ]);
    }

    #[Route('/{id}', name: 'badge_show', methods: ['GET'])]
    public function show(Badge $badge): Response
    {
        return $this->render('badge/show.html.twig', [
            'badge' => $badge,
        ]);
    }

    #[Route('/{id}/edit', name: 'badge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Badge $badge): Response
    {
        if ($request->isMethod('POST')) {
            $badge->setName($request->request->get('name'));
            $badge->setDescription($request->request->get('description'));
            $badge->setIcon($request->request->get('icon'));
            $badge->setCategory($request->request->get('category'));
            $badge->setXpReward((int) $request->request->get('xp_reward'));

            // Parse criteria JSON
            $criteriaJson = $request->request->get('criteria');
            if ($criteriaJson) {
                try {
                    $criteria = json_decode($criteriaJson, true, 512, JSON_THROW_ON_ERROR);
                    $badge->setCriteria($criteria);
                } catch (JsonException) {
                    $this->addFlash('error', 'Format JSON invalide pour les critères');

                    return $this->render('badge/edit.html.twig', [
                        'badge'      => $badge,
                        'categories' => $this->getBadgeCategories(),
                    ]);
                }
            } else {
                $badge->setCriteria(null);
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le badge a été mis à jour avec succès');

            return $this->redirectToRoute('badge_index');
        }

        return $this->render('badge/edit.html.twig', [
            'badge'      => $badge,
            'categories' => $this->getBadgeCategories(),
        ]);
    }

    #[Route('/{id}/toggle', name: 'badge_toggle', methods: ['POST'])]
    public function toggle(Request $request, Badge $badge): Response
    {
        if (!$this->isCsrfTokenValid('badge_toggle'.$badge->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('badge_index');
        }

        $badge->setActive(!$badge->isActive());
        $this->entityManager->flush();

        $status = $badge->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', sprintf('Le badge "%s" a été %s', $badge->getName(), $status));

        return $this->redirectToRoute('badge_index');
    }

    #[Route('/{id}/delete', name: 'badge_delete', methods: ['POST'])]
    public function delete(Request $request, Badge $badge): Response
    {
        if (!$this->isCsrfTokenValid('badge_delete'.$badge->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('badge_index');
        }

        $this->entityManager->remove($badge);
        $this->entityManager->flush();

        $this->addFlash('success', 'Le badge a été supprimé avec succès');

        return $this->redirectToRoute('badge_index');
    }

    private function getBadgeCategories(): array
    {
        return [
            'contribution'  => 'Contribution',
            'engagement'    => 'Engagement',
            'expertise'     => 'Expertise',
            'collaboration' => 'Collaboration',
            'performance'   => 'Performance',
            'anciennete'    => 'Ancienneté',
        ];
    }
}
