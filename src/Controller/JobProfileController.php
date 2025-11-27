<?php

namespace App\Controller;

use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/job-profiles')]
#[IsGranted('ROLE_MANAGER')]
class JobProfileController extends AbstractController
{
    #[Route('', name: 'job_profile_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $profiles = $em->getRepository(Profile::class)->findBy([], ['name' => 'ASC']);

        return $this->render('job_profile/index.html.twig', [
            'profiles' => $profiles,
        ]);
    }

    #[Route('/new', name: 'job_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $profile = new Profile();

        if ($request->isMethod('POST')) {
            $profile->setName($request->request->get('name'));
            $profile->setDescription($request->request->get('description'));
            $profile->setDefaultDailyRate($request->request->get('default_daily_rate'));
            $profile->setCjm($request->request->get('cjm'));
            $profile->setMarginCoefficient($request->request->get('margin_coefficient') ?: '1.00');
            $profile->setColor($request->request->get('color'));
            $profile->setActive($request->request->get('active', false));

            $em->persist($profile);
            $em->flush();

            $this->addFlash('success', 'Profil métier créé avec succès');

            return $this->redirectToRoute('job_profile_index');
        }

        return $this->render('job_profile/new.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/{id}', name: 'job_profile_show', methods: ['GET'])]
    public function show(Profile $profile): Response
    {
        return $this->render('job_profile/show.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/{id}/edit', name: 'job_profile_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Profile $profile, EntityManagerInterface $em): Response
    {
        if ($request->isMethod('POST')) {
            $profile->setName($request->request->get('name'));
            $profile->setDescription($request->request->get('description'));
            $profile->setDefaultDailyRate($request->request->get('default_daily_rate'));
            $profile->setCjm($request->request->get('cjm'));
            $profile->setMarginCoefficient($request->request->get('margin_coefficient') ?: '1.00');
            $profile->setColor($request->request->get('color'));
            $profile->setActive($request->request->get('active', false));

            $em->flush();

            $this->addFlash('success', 'Profil métier modifié avec succès');

            return $this->redirectToRoute('job_profile_index');
        }

        return $this->render('job_profile/edit.html.twig', [
            'profile' => $profile,
        ]);
    }

    #[Route('/{id}/delete', name: 'job_profile_delete', methods: ['POST'])]
    public function delete(Request $request, Profile $profile, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$profile->getId(), $request->request->get('_token'))) {
            $em->remove($profile);
            $em->flush();
            $this->addFlash('success', 'Profil métier supprimé avec succès');
        }

        return $this->redirectToRoute('job_profile_index');
    }
}
