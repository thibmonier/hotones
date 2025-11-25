<?php

namespace App\Controller;

use App\Entity\Profile;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/job-profiles')]
#[IsGranted('ROLE_MANAGER')]
class JobProfileController extends AbstractController
{
    #[Route('', name: 'job_profile_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator): Response
    {
        // Filtres
        $search = $request->query->get('search', '');
        $active = $request->query->get('active', '');

        // Query builder avec filtres
        $qb = $em->getRepository(Profile::class)->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC');

        if ($search) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($active !== '') {
            $qb->andWhere('p.active = :active')
                ->setParameter('active', (bool) $active);
        }

        // Pagination
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            $request->query->getInt('per_page', 25),
        );

        return $this->render('job_profile/index.html.twig', [
            'profiles' => $pagination,
            'filters'  => [
                'search' => $search,
                'active' => $active,
            ],
        ]);
    }

    #[Route('/export', name: 'job_profile_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $em): Response
    {
        // Mêmes filtres que l'index
        $search = $request->query->get('search', '');
        $active = $request->query->get('active', '');

        $qb = $em->getRepository(Profile::class)->createQueryBuilder('p')
            ->orderBy('p.name', 'ASC');

        if ($search) {
            $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        if ($active !== '') {
            $qb->andWhere('p.active = :active')
                ->setParameter('active', (bool) $active);
        }

        $profiles = $qb->getQuery()->getResult();

        // Génération CSV
        $csv = "Nom;Description;TJM par défaut;Couleur;Statut\n";
        foreach ($profiles as $profile) {
            $csv .= sprintf(
                "%s;%s;%s;%s;%s\n",
                $profile->getName(),
                $profile->getDescription()      ?? '',
                $profile->getDefaultDailyRate() ?? '',
                $profile->getColor()            ?? '',
                $profile->getActive() ? 'Actif' : 'Inactif',
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'profils_metier_'.date('Y-m-d').'.csv',
        ));

        return $response;
    }

    #[Route('/new', name: 'job_profile_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $profile = new Profile();

        if ($request->isMethod('POST')) {
            $profile->setName($request->request->get('name'));
            $profile->setDescription($request->request->get('description'));
            $profile->setDefaultDailyRate($request->request->get('default_daily_rate'));
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
