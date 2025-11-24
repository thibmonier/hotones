<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Skill;
use App\Form\SkillType;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/skills')]
#[IsGranted('ROLE_ADMIN')]
class SkillController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private SkillRepository $skillRepository
    ) {
    }

    #[Route('', name: 'skill_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $category     = $request->query->get('category');
        $search       = $request->query->get('search');
        $showInactive = (bool) $request->query->get('show_inactive', false);

        if ($search) {
            $skills = $this->skillRepository->search($search);
        } elseif ($category) {
            $skills = $this->skillRepository->findByCategory($category);
        } else {
            $qb = $this->skillRepository->createQueryBuilder('s');
            if (!$showInactive) {
                $qb->where('s.active = :active')->setParameter('active', true);
            }
            $qb->orderBy('s.category', 'ASC')->addOrderBy('s.name', 'ASC');
            $skills = $qb->getQuery()->getResult();
        }

        // Statistiques
        $countByCategory      = $this->skillRepository->countByCategory();
        $withContributorCount = $this->skillRepository->findWithContributorCount();

        return $this->render('skill/index.html.twig', [
            'skills'          => $skills,
            'countByCategory' => $countByCategory,
            'skillsWithCount' => $withContributorCount,
            'filters'         => [
                'category'      => $category,
                'search'        => $search,
                'show_inactive' => $showInactive,
            ],
        ]);
    }

    #[Route('/new', name: 'skill_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $skill = new Skill();
        $form  = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->persist($skill);
            $this->em->flush();

            $this->addFlash('success', 'Compétence créée avec succès');

            return $this->redirectToRoute('skill_index');
        }

        return $this->render('skill/new.html.twig', [
            'skill' => $skill,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}', name: 'skill_show', methods: ['GET'])]
    public function show(Skill $skill): Response
    {
        // Récupérer les contributeurs ayant cette compétence
        $contributorSkills = $this->em->getRepository(\App\Entity\ContributorSkill::class)
            ->findBySkill($skill);

        // Distribution des niveaux
        $levelDistribution = $this->em->getRepository(\App\Entity\ContributorSkill::class)
            ->getLevelDistributionForSkill($skill);

        return $this->render('skill/show.html.twig', [
            'skill'             => $skill,
            'contributorSkills' => $contributorSkills,
            'levelDistribution' => $levelDistribution,
        ]);
    }

    #[Route('/{id}/edit', name: 'skill_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Skill $skill): Response
    {
        $form = $this->createForm(SkillType::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->em->flush();

            $this->addFlash('success', 'Compétence modifiée avec succès');

            return $this->redirectToRoute('skill_index');
        }

        return $this->render('skill/edit.html.twig', [
            'skill' => $skill,
            'form'  => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'skill_delete', methods: ['POST'])]
    public function delete(Request $request, Skill $skill): Response
    {
        if ($this->isCsrfTokenValid('delete'.$skill->getId(), $request->request->get('_token'))) {
            // Vérifier si la compétence est utilisée
            if ($skill->getContributorCount() > 0) {
                $this->addFlash('warning', 'Cette compétence est utilisée par des contributeurs. Elle a été désactivée.');
                $skill->setActive(false);
                $this->em->flush();
            } else {
                $this->em->remove($skill);
                $this->em->flush();
                $this->addFlash('success', 'Compétence supprimée avec succès');
            }
        }

        return $this->redirectToRoute('skill_index');
    }

    #[Route('/{id}/toggle', name: 'skill_toggle', methods: ['POST'])]
    public function toggle(Request $request, Skill $skill): Response
    {
        if ($this->isCsrfTokenValid('toggle'.$skill->getId(), $request->request->get('_token'))) {
            $skill->setActive(!$skill->isActive());
            $this->em->flush();

            $this->addFlash('success', sprintf(
                'Compétence %s avec succès',
                $skill->isActive() ? 'activée' : 'désactivée',
            ));
        }

        return $this->redirectToRoute('skill_index');
    }
}
