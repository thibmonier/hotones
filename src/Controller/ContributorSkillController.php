<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\ContributorSkill;
use App\Form\ContributorSkillType;
use App\Repository\ContributorSkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contributors/{contributorId}/skills')]
#[IsGranted('ROLE_CHEF_PROJET')]
class ContributorSkillController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private ContributorSkillRepository $contributorSkillRepository
    ) {
    }

    #[Route('', name: 'contributor_skill_index', methods: ['GET'])]
    public function index(int $contributorId): Response
    {
        $contributor = $this->em->getRepository(Contributor::class)->find($contributorId);
        if (!$contributor) {
            throw $this->createNotFoundException('Collaborateur non trouvé');
        }

        $skillsByCategory = $this->contributorSkillRepository->findByContributorGroupedByCategory($contributor);
        $countByLevel     = $this->contributorSkillRepository->countByLevelForContributor($contributor);

        return $this->render('contributor_skill/index.html.twig', [
            'contributor'      => $contributor,
            'skillsByCategory' => $skillsByCategory,
            'countByLevel'     => $countByLevel,
        ]);
    }

    #[Route('/new', name: 'contributor_skill_new', methods: ['GET', 'POST'])]
    public function new(Request $request, int $contributorId): Response
    {
        $contributor = $this->em->getRepository(Contributor::class)->find($contributorId);
        if (!$contributor) {
            throw $this->createNotFoundException('Collaborateur non trouvé');
        }

        $contributorSkill = new ContributorSkill();
        $contributorSkill->setContributor($contributor);

        $form = $this->createForm(ContributorSkillType::class, $contributorSkill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($contributorSkill);
            $this->em->flush();

            $this->addFlash('success', 'Compétence ajoutée avec succès');

            return $this->redirectToRoute('contributor_skill_index', ['contributorId' => $contributorId]);
        }

        return $this->render('contributor_skill/new.html.twig', [
            'contributor'      => $contributor,
            'contributorSkill' => $contributorSkill,
            'form'             => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'contributor_skill_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, int $contributorId, ContributorSkill $contributorSkill): Response
    {
        // Vérifier que la compétence appartient bien au collaborateur
        if ($contributorSkill->getContributor()->getId() !== $contributorId) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(ContributorSkillType::class, $contributorSkill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Compétence modifiée avec succès');

            return $this->redirectToRoute('contributor_skill_index', ['contributorId' => $contributorId]);
        }

        return $this->render('contributor_skill/edit.html.twig', [
            'contributor'      => $contributorSkill->getContributor(),
            'contributorSkill' => $contributorSkill,
            'form'             => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'contributor_skill_delete', methods: ['POST'])]
    public function delete(Request $request, int $contributorId, ContributorSkill $contributorSkill): Response
    {
        // Vérifier que la compétence appartient bien au collaborateur
        if ($contributorSkill->getContributor()->getId() !== $contributorId) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$contributorSkill->getId(), $request->request->get('_token'))) {
            $this->em->remove($contributorSkill);
            $this->em->flush();

            $this->addFlash('success', 'Compétence supprimée avec succès');
        }

        return $this->redirectToRoute('contributor_skill_index', ['contributorId' => $contributorId]);
    }

    #[Route('/{id}/assess', name: 'contributor_skill_assess', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function assess(Request $request, int $contributorId, ContributorSkill $contributorSkill): Response
    {
        // Vérifier que la compétence appartient bien au collaborateur
        if ($contributorSkill->getContributor()->getId() !== $contributorId) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('assess'.$contributorSkill->getId(), $request->request->get('_token'))) {
            $managerLevel = (int) $request->request->get('manager_level');
            $notes        = $request->request->get('notes');

            if ($managerLevel >= ContributorSkill::LEVEL_BEGINNER && $managerLevel <= ContributorSkill::LEVEL_EXPERT) {
                $contributorSkill->setManagerAssessmentLevel($managerLevel);
                if ($notes) {
                    $contributorSkill->setNotes($notes);
                }
                $this->em->flush();

                $this->addFlash('success', 'Évaluation enregistrée avec succès');
            } else {
                $this->addFlash('error', 'Niveau d\'évaluation invalide');
            }
        }

        return $this->redirectToRoute('contributor_skill_index', ['contributorId' => $contributorId]);
    }
}
