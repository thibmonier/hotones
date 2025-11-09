<?php

namespace App\Controller;

use App\Entity\Project;
use App\Entity\ProjectTechnology;
use App\Form\ProjectTechnologyType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/projects')]
#[IsGranted('ROLE_CHEF_PROJET')]
class ProjectTechnologyController extends AbstractController
{
    #[Route('/{id}/tech', name: 'project_tech_index', methods: ['GET', 'POST'])]
    public function manage(Project $project, Request $request, EntityManagerInterface $em): Response
    {
        // Formulaire d'ajout
        $pt = new ProjectTechnology();
        $pt->setProject($project);
        $form = $this->createForm(ProjectTechnologyType::class, $pt);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Empêcher doublons (unique project+technology)
            foreach ($project->getProjectTechnologies() as $existing) {
                if ($existing->getTechnology() === $pt->getTechnology()) {
                    $this->addFlash('warning', 'Cette technologie existe déjà. Modifiez la version au besoin.');

                    return $this->redirectToRoute('project_tech_index', ['id' => $project->getId()]);
                }
            }

            $em->persist($pt);
            $em->flush();
            $this->addFlash('success', 'Technologie ajoutée');

            return $this->redirectToRoute('project_tech_index', ['id' => $project->getId()]);
        }

        return $this->render('project/tech/index.html.twig', [
            'project' => $project,
            'form'    => $form->createView(),
        ]);
    }

    #[Route('/tech/{id}/edit', name: 'project_tech_edit', methods: ['GET', 'POST'])]
    public function edit(ProjectTechnology $pt, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ProjectTechnologyType::class, $pt);
        // Verrouiller le choix de la techno à l'édition
        $form->remove('technology');
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Version mise à jour');

            return $this->redirectToRoute('project_tech_index', ['id' => $pt->getProject()->getId()]);
        }

        return $this->render('project/tech/edit.html.twig', [
            'pt'   => $pt,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/tech/{id}/delete', name: 'project_tech_delete', methods: ['POST'])]
    public function delete(ProjectTechnology $pt, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('delete_pt'.$pt->getId(), $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide');
        }
        $projectId = $pt->getProject()->getId();
        $em->remove($pt);
        $em->flush();
        $this->addFlash('success', 'Technologie retirée');

        return $this->redirectToRoute('project_tech_index', ['id' => $projectId]);
    }
}
