<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\ContributorTechnology;
use App\Form\ContributorTechnologyType;
use App\Repository\ContributorTechnologyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contributors/{contributorId}/technologies')]
#[IsGranted('ROLE_CHEF_PROJET')]
class ContributorTechnologyController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ContributorTechnologyRepository $contributorTechnologyRepository,
    ) {
    }

    #[Route('', name: 'contributor_technology_index', methods: ['GET'])]
    public function index(int $contributorId): Response
    {
        $contributor = $this->em->getRepository(Contributor::class)->find($contributorId);
        if (!$contributor) {
            throw $this->createNotFoundException('Collaborateur non trouvé');
        }

        $technologiesByCategory = $this->contributorTechnologyRepository->findByContributorGroupedByCategory($contributor);
        $countByLevel           = $this->getCountByLevel($contributor);

        return $this->render('contributor_technology/index.html.twig', [
            'contributor'            => $contributor,
            'technologiesByCategory' => $technologiesByCategory,
            'countByLevel'           => $countByLevel,
        ]);
    }

    #[Route('/new', name: 'contributor_technology_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $contributorId): Response
    {
        $contributor = $this->em->getRepository(Contributor::class)->find($contributorId);
        if (!$contributor) {
            throw $this->createNotFoundException('Collaborateur non trouvé');
        }

        $contributorTechnology = new ContributorTechnology();
        $contributorTechnology->setCompany($contributor->getCompany());
        $contributorTechnology->setContributor($contributor);
        $contributorTechnology->setWantsToUse(true);

        $form = $this->createForm(ContributorTechnologyType::class, $contributorTechnology);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($contributorTechnology);
            $this->em->flush();

            $this->addFlash('success', 'Technologie ajoutée avec succès');

            return $this->redirectToRoute('contributor_technology_index', ['contributorId' => $contributorId]);
        }

        return $this->render('contributor_technology/new.html.twig', [
            'contributor'           => $contributor,
            'contributorTechnology' => $contributorTechnology,
            'form'                  => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'contributor_technology_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $contributorId, ContributorTechnology $contributorTechnology): Response
    {
        if ($contributorTechnology->getContributor()?->getId() !== $contributorId) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ContributorTechnologyType::class, $contributorTechnology);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Technologie modifiée avec succès');

            return $this->redirectToRoute('contributor_technology_index', ['contributorId' => $contributorId]);
        }

        return $this->render('contributor_technology/edit.html.twig', [
            'contributor'           => $contributorTechnology->getContributor(),
            'contributorTechnology' => $contributorTechnology,
            'form'                  => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'contributor_technology_delete', methods: ['POST'])]
    public function delete(Request $request, int $contributorId, ContributorTechnology $contributorTechnology): Response
    {
        if ($contributorTechnology->getContributor()?->getId() !== $contributorId) {
            throw $this->createAccessDeniedException();
        }

        if (
            $this->isCsrfTokenValid(
                'delete'.$contributorTechnology->getId(),
                $request->getPayload()->getString('_token'),
            )
        ) {
            $this->em->remove($contributorTechnology);
            $this->em->flush();

            $this->addFlash('success', 'Technologie supprimée avec succès');
        }

        return $this->redirectToRoute('contributor_technology_index', ['contributorId' => $contributorId]);
    }

    #[Route('/{id}/assess', name: 'contributor_technology_assess', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function assess(Request $request, int $contributorId, ContributorTechnology $contributorTechnology): Response
    {
        if ($contributorTechnology->getContributor()?->getId() !== $contributorId) {
            throw $this->createAccessDeniedException();
        }

        if (
            $this->isCsrfTokenValid(
                'assess'.$contributorTechnology->getId(),
                $request->getPayload()->getString('_token'),
            )
        ) {
            $managerLevel = $request->getPayload()->getInt('manager_level');
            $notes        = $request->getPayload()->getString('notes');

            if (
                $managerLevel >= ContributorTechnology::LEVEL_BEGINNER
                && $managerLevel <= ContributorTechnology::LEVEL_EXPERT
            ) {
                $contributorTechnology->setManagerAssessmentLevel($managerLevel);
                if ($notes) {
                    $contributorTechnology->setNotes($notes);
                }
                $this->em->flush();

                $this->addFlash('success', 'Évaluation enregistrée avec succès');
            } else {
                $this->addFlash('error', 'Niveau d\'évaluation invalide');
            }
        }

        return $this->redirectToRoute('contributor_technology_index', ['contributorId' => $contributorId]);
    }

    /**
     * @return array<int, int>
     */
    private function getCountByLevel(Contributor $contributor): array
    {
        $countByLevel = [1 => 0, 2 => 0, 3 => 0, 4 => 0];

        foreach ($contributor->getContributorTechnologies() as $ct) {
            $level = $ct->getEffectiveLevel();
            if (isset($countByLevel[$level])) {
                ++$countByLevel[$level];
            }
        }

        return $countByLevel;
    }
}
