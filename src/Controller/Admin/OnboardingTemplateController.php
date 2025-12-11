<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\OnboardingTemplate;
use App\Repository\OnboardingTemplateRepository;
use App\Repository\ProfileRepository;
use App\Service\OnboardingService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/onboarding-templates')]
#[IsGranted('ROLE_ADMIN')]
class OnboardingTemplateController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly OnboardingTemplateRepository $templateRepository,
        private readonly ProfileRepository $profileRepository,
        private readonly OnboardingService $onboardingService,
    ) {
    }

    #[Route('', name: 'admin_onboarding_template_index', methods: ['GET'])]
    public function index(): Response
    {
        $templates = $this->templateRepository->findAll();

        return $this->render('admin/onboarding_template/index.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/create', name: 'admin_onboarding_template_create', methods: ['GET', 'POST'])]
    public function create(Request $request): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('create-template', $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');

                return $this->redirectToRoute('admin_onboarding_template_create');
            }

            $name        = $request->request->get('name');
            $description = $request->request->get('description');
            $profileId   = $request->request->get('profile_id');
            $tasksData   = $request->request->all('tasks');

            // Validation
            if (empty($name)) {
                $this->addFlash('error', 'Le nom est requis.');

                return $this->redirectToRoute('admin_onboarding_template_create');
            }

            // Parse tasks
            $tasks = [];
            if (!empty($tasksData)) {
                $order = 0;
                foreach ($tasksData as $taskData) {
                    if (!empty($taskData['title'])) {
                        $tasks[] = [
                            'title'            => $taskData['title'],
                            'description'      => $taskData['description'] ?? '',
                            'assigned_to'      => $taskData['assigned_to'] ?? 'contributor',
                            'type'             => $taskData['type']        ?? 'action',
                            'days_after_start' => (int) ($taskData['days_after_start'] ?? 0),
                            'order'            => $order++,
                        ];
                    }
                }
            }

            try {
                $template = $this->onboardingService->createTemplate(
                    $name,
                    $description,
                    $profileId ? (int) $profileId : null,
                    $tasks,
                );

                $this->addFlash('success', "Template '{$name}' créé avec succès.");

                return $this->redirectToRoute('admin_onboarding_template_show', ['id' => $template->getId()]);
            } catch (Exception $e) {
                $this->addFlash('error', 'Erreur lors de la création du template : '.$e->getMessage());

                return $this->redirectToRoute('admin_onboarding_template_create');
            }
        }

        $profiles = $this->profileRepository->findAll();

        return $this->render('admin/onboarding_template/create.html.twig', [
            'profiles' => $profiles,
        ]);
    }

    #[Route('/{id}', name: 'admin_onboarding_template_show', methods: ['GET'])]
    public function show(OnboardingTemplate $template): Response
    {
        return $this->render('admin/onboarding_template/show.html.twig', [
            'template' => $template,
        ]);
    }

    #[Route('/{id}/edit', name: 'admin_onboarding_template_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, OnboardingTemplate $template): Response
    {
        if ($request->isMethod('POST')) {
            if (!$this->isCsrfTokenValid('edit-template-'.$template->getId(), $request->request->get('_token'))) {
                $this->addFlash('error', 'Token CSRF invalide.');

                return $this->redirectToRoute('admin_onboarding_template_edit', ['id' => $template->getId()]);
            }

            $name        = $request->request->get('name');
            $description = $request->request->get('description');
            $profileId   = $request->request->get('profile_id');
            $active      = (bool) $request->request->get('active', false);
            $tasksData   = $request->request->all('tasks');

            // Validation
            if (empty($name)) {
                $this->addFlash('error', 'Le nom est requis.');

                return $this->redirectToRoute('admin_onboarding_template_edit', ['id' => $template->getId()]);
            }

            // Parse tasks
            $tasks = [];
            if (!empty($tasksData)) {
                $order = 0;
                foreach ($tasksData as $taskData) {
                    if (!empty($taskData['title'])) {
                        $tasks[] = [
                            'title'            => $taskData['title'],
                            'description'      => $taskData['description'] ?? '',
                            'assigned_to'      => $taskData['assigned_to'] ?? 'contributor',
                            'type'             => $taskData['type']        ?? 'action',
                            'days_after_start' => (int) ($taskData['days_after_start'] ?? 0),
                            'order'            => $order++,
                        ];
                    }
                }
            }

            try {
                $profile = null;
                if ($profileId) {
                    $profile = $this->profileRepository->find($profileId);
                }

                $template->setName($name);
                $template->setDescription($description);
                $template->setProfile($profile);
                $template->setActive($active);
                $template->setTasks($tasks);

                $this->em->flush();

                $this->addFlash('success', "Template '{$name}' mis à jour avec succès.");

                return $this->redirectToRoute('admin_onboarding_template_show', ['id' => $template->getId()]);
            } catch (Exception $e) {
                $this->addFlash('error', 'Erreur lors de la mise à jour : '.$e->getMessage());

                return $this->redirectToRoute('admin_onboarding_template_edit', ['id' => $template->getId()]);
            }
        }

        $profiles = $this->profileRepository->findAll();

        return $this->render('admin/onboarding_template/edit.html.twig', [
            'template' => $template,
            'profiles' => $profiles,
        ]);
    }

    #[Route('/{id}/duplicate', name: 'admin_onboarding_template_duplicate', methods: ['POST'])]
    public function duplicate(Request $request, OnboardingTemplate $template): Response
    {
        if (!$this->isCsrfTokenValid('duplicate-template-'.$template->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_onboarding_template_show', ['id' => $template->getId()]);
        }

        $newName = $request->request->get('new_name', $template->getName().' (Copie)');

        try {
            $duplicate = $this->onboardingService->duplicateTemplate($template, $newName);

            $this->addFlash('success', "Template dupliqué avec succès : '{$newName}'.");

            return $this->redirectToRoute('admin_onboarding_template_show', ['id' => $duplicate->getId()]);
        } catch (Exception $e) {
            $this->addFlash('error', 'Erreur lors de la duplication : '.$e->getMessage());

            return $this->redirectToRoute('admin_onboarding_template_show', ['id' => $template->getId()]);
        }
    }

    #[Route('/{id}/toggle', name: 'admin_onboarding_template_toggle', methods: ['POST'])]
    public function toggle(Request $request, OnboardingTemplate $template): Response
    {
        if (!$this->isCsrfTokenValid('toggle-template-'.$template->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_onboarding_template_index');
        }

        $template->setActive(!$template->isActive());
        $this->em->flush();

        $status = $template->isActive() ? 'activé' : 'désactivé';
        $this->addFlash('success', "Template '{$template->getName()}' {$status}.");

        return $this->redirectToRoute('admin_onboarding_template_index');
    }

    #[Route('/{id}/delete', name: 'admin_onboarding_template_delete', methods: ['POST'])]
    public function delete(Request $request, OnboardingTemplate $template): Response
    {
        if (!$this->isCsrfTokenValid('delete-template-'.$template->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('admin_onboarding_template_index');
        }

        $name = $template->getName();

        try {
            $this->em->remove($template);
            $this->em->flush();

            $this->addFlash('success', "Template '{$name}' supprimé avec succès.");
        } catch (Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : '.$e->getMessage());
        }

        return $this->redirectToRoute('admin_onboarding_template_index');
    }
}
