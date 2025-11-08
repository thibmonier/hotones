<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Contributor;
use App\Form\ContributorType;
use App\Repository\ContributorRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/contributors')]
#[IsGranted('ROLE_CHEF_PROJET')]
class ContributorController extends AbstractController
{
    public function __construct(
        private readonly ContributorRepository $contributorRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SluggerInterface $slugger,
    ) {
    }

    #[Route('', name: 'contributor_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $search = $request->query->get('search', '');
        $active = $request->query->get('active', 'all');

        $qb = $this->contributorRepository->createQueryBuilder('c')
            ->leftJoin('c.profiles', 'p')
            ->addSelect('p')
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC');

        if ($search) {
            $qb->andWhere('c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        if ($active !== 'all') {
            $qb->andWhere('c.active = :active')
               ->setParameter('active', $active === 'active');
        }

        $contributors = $qb->getQuery()->getResult();

        return $this->render('contributor/index.html.twig', [
            'contributors' => $contributors,
            'search'       => $search,
            'active'       => $active,
        ]);
    }

    #[Route('/new', name: 'contributor_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function new(Request $request): Response
    {
        $contributor = new Contributor();
        $form        = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle avatar upload
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $contributor->setAvatarFilename($this->handleAvatarUpload($avatarFile));
            }

            $this->entityManager->persist($contributor);
            $this->entityManager->flush();

            $this->addFlash('success', 'Le contributeur a été créé avec succès.');

            return $this->redirectToRoute('contributor_show', ['id' => $contributor->getId()]);
        }

        return $this->render('contributor/new.html.twig', [
            'contributor' => $contributor,
            'form'        => $form,
        ]);
    }

    #[Route('/{id}', name: 'contributor_show', methods: ['GET'])]
    public function show(Contributor $contributor): Response
    {
        return $this->render('contributor/show.html.twig', [
            'contributor' => $contributor,
        ]);
    }

    #[Route('/{id}/edit', name: 'contributor_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function edit(Request $request, Contributor $contributor): Response
    {
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Handle avatar upload
            /** @var UploadedFile $avatarFile */
            $avatarFile = $form->get('avatarFile')->getData();
            if ($avatarFile) {
                $contributor->setAvatarFilename($this->handleAvatarUpload($avatarFile));
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Le contributeur a été modifié avec succès.');

            return $this->redirectToRoute('contributor_show', ['id' => $contributor->getId()]);
        }

        return $this->render('contributor/edit.html.twig', [
            'contributor' => $contributor,
            'form'        => $form,
        ]);
    }

    #[Route('/{id}/employment-periods', name: 'contributor_employment_periods', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function employmentPeriods(Contributor $contributor): Response
    {
        $periods = $this->entityManager->getRepository(\App\Entity\EmploymentPeriod::class)
            ->findBy(['contributor' => $contributor], ['startDate' => 'DESC']);

        return $this->render('contributor/employment_periods.html.twig', [
            'contributor' => $contributor,
            'periods'     => $periods,
        ]);
    }

    #[Route('/{id}/timesheets', name: 'contributor_timesheets', methods: ['GET'])]
    public function timesheets(Request $request, Contributor $contributor): Response
    {
        $month     = $request->query->get('month', date('Y-m'));
        $startDate = new DateTime($month.'-01');
        $endDate   = clone $startDate;
        $endDate->modify('last day of this month');

        $timesheetRepo = $this->entityManager->getRepository(\App\Entity\Timesheet::class);
        $timesheets    = $timesheetRepo->findByContributorAndDateRange($contributor, $startDate, $endDate);
        $projectTotals = $timesheetRepo->getHoursGroupedByProjectForContributor($contributor, $startDate, $endDate);
        $totalHours    = array_sum(array_map(fn ($t) => $t->getHours(), $timesheets));

        return $this->render('contributor/timesheets.html.twig', [
            'contributor'   => $contributor,
            'timesheets'    => $timesheets,
            'totalHours'    => $totalHours,
            'projectTotals' => $projectTotals,
            'month'         => $month,
            'startDate'     => $startDate,
            'endDate'       => $endDate,
        ]);
    }

    #[Route('/{id}/delete', name: 'contributor_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(Request $request, Contributor $contributor): Response
    {
        if ($this->isCsrfTokenValid('delete'.$contributor->getId(), $request->request->get('_token'))) {
            // Soft delete - marquer comme inactif au lieu de supprimer
            $contributor->setActive(false);
            $this->entityManager->flush();
            $this->addFlash('success', 'Contributeur désactivé avec succès');
        }

        return $this->redirectToRoute('contributor_index');
    }

    private function handleAvatarUpload(UploadedFile $file): string
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename     = $this->slugger->slug($originalFilename);
        $newFilename      = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        $file->move(
            $this->getParameter('avatars_directory'),
            $newFilename,
        );

        return $newFilename;
    }
}
