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
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);
        if ($reset && $session) {
            $session->remove('contributor_filters');

            return $this->redirectToRoute('contributor_index');
        }

        $queryAll  = $request->query->all();
        $keys      = ['search', 'active'];
        $hasFilter = count(array_intersect(array_keys($queryAll), $keys)) > 0;
        $saved     = ($session && $session->has('contributor_filters')) ? (array) $session->get('contributor_filters') : [];

        $search = $hasFilter ? ($request->query->get('search', '')) : ($saved['search'] ?? '');
        $active = $hasFilter ? ($request->query->get('active', 'all')) : ($saved['active'] ?? 'all');

        $qb = $this->contributorRepository->createQueryBuilder('c')
            ->leftJoin('c.profiles', 'p')
            ->addSelect('p');

        if ($search) {
            $qb->andWhere('c.firstName LIKE :search OR c.lastName LIKE :search OR c.email LIKE :search')
               ->setParameter('search', '%'.$search.'%');
        }

        if ($active !== 'all') {
            $qb->andWhere('c.active = :active')
               ->setParameter('active', $active === 'active');
        }

        // Tri
        $sort = $hasFilter ? ($request->query->get('sort', 'name')) : ($saved['sort'] ?? 'name');
        $dir  = $hasFilter ? ($request->query->get('dir', 'ASC')) : ($saved['dir'] ?? 'ASC');
        $map  = [
            'name'   => ['c.lastName', 'c.firstName'],
            'email'  => ['c.email'],
            'active' => ['c.active'],
        ];
        $columns   = $map[$sort] ?? ['c.lastName', 'c.firstName'];
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $first     = true;
        foreach ($columns as $col) {
            if ($first) {
                $qb->orderBy($col, $direction);
                $first = false;
            } else {
                $qb->addOrderBy($col, $direction);
            }
        }

        // Pagination
        $allowedPerPage = [10, 20, 50, 100];
        $perPageParam   = (int) $request->query->get('per_page', $saved['per_page'] ?? 20);
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 20;
        $page           = max(1, (int) $request->query->get('page', 1));
        $offset         = ($page - 1) * $perPage;

        $countQb = clone $qb;
        $total   = (int) $countQb->select('COUNT(DISTINCT c.id)')->getQuery()->getSingleScalarResult();

        // Stats globales (sur l'ensemble filtré)
        $withUserQb = clone $qb;
        $withUser   = (int) $withUserQb
            ->select('COUNT(DISTINCT c.id)')
            ->andWhere('c.user IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $avgCjmQb = clone $qb;
        $avgCjm   = $avgCjmQb
            ->select('AVG(c.cjm)')
            ->andWhere('c.cjm IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
        $avgCjm = $avgCjm !== null ? (float) $avgCjm : null;

        $avgTjmQb = clone $qb;
        $avgTjm   = $avgTjmQb
            ->select('AVG(c.tjm)')
            ->andWhere('c.tjm IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
        $avgTjm = $avgTjm !== null ? (float) $avgTjm : null;

        $qb->setFirstResult($offset)->setMaxResults($perPage);
        $contributors = $qb->getQuery()->getResult();

        $pagination = [
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'total_pages'  => (int) ceil($total / $perPage),
            'has_prev'     => $page > 1,
            'has_next'     => $page * $perPage < $total,
        ];

        if ($session) {
            $session->set('contributor_filters', [
                'search'   => $search,
                'active'   => $active,
                'sort'     => $sort,
                'dir'      => $direction,
                'per_page' => $perPage,
            ]);
        }

        return $this->render('contributor/index.html.twig', [
            'contributors'       => $contributors,
            'search'             => $search,
            'active'             => $active,
            'sort'               => $sort,
            'dir'                => $direction,
            'pagination'         => $pagination,
            'stats_linked_users' => $withUser,
            'stats_avg_cjm'      => $avgCjm,
            'stats_avg_tjm'      => $avgTjm,
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

        $uploadDirectory = $this->getParameter('avatars_directory');

        // Créer le répertoire s'il n'existe pas
        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        $file->move($uploadDirectory, $newFilename);

        return $newFilename;
    }

    #[Route('/export.csv', name: 'contributor_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $session = $request->getSession();
        $saved   = ($session && $session->has('contributor_filters')) ? (array) $session->get('contributor_filters') : [];
        $search  = $request->query->get('search', $saved['search'] ?? '');
        $active  = $request->query->get('active', $saved['active'] ?? 'all');
        $sort    = $request->query->get('sort', $saved['sort'] ?? 'name');
        $dir     = $request->query->get('dir', $saved['dir'] ?? 'ASC');

        $qb = $this->contributorRepository->createQueryBuilder('c')
            ->leftJoin('c.profiles', 'p')->addSelect('p');
        if ($search) {
            $qb->andWhere('c.firstName LIKE :s OR c.lastName LIKE :s OR c.email LIKE :s')->setParameter('s', '%'.$search.'%');
        }
        if ($active !== 'all') {
            $qb->andWhere('c.active = :a')->setParameter('a', $active === 'active');
        }
        $map       = ['name' => ['c.lastName', 'c.firstName'], 'email' => ['c.email'], 'active' => ['c.active']];
        $cols      = $map[$sort] ?? ['c.lastName', 'c.firstName'];
        $direction = strtoupper($dir) === 'DESC' ? 'DESC' : 'ASC';
        $first     = true;
        foreach ($cols as $col) {
            if ($first) {
                $qb->orderBy($col, $direction);
                $first = false;
            } else {
                $qb->addOrderBy($col, $direction);
            }
        }
        $contributors = $qb->getQuery()->getResult();

        $rows   = [];
        $header = ['Nom', 'Email', 'Téléphone pro', 'Téléphone perso', 'Actif', 'Profils', 'CJM', 'TJM'];
        $rows[] = $header;
        foreach ($contributors as $c) {
            $profiles = [];
            foreach ($c->getProfiles() as $prof) {
                $profiles[] = $prof->getName();
            }
            $rows[] = [
                method_exists($c, 'getName') ? $c->getName() : trim(($c->getFirstName() ?: '').' '.($c->getLastName() ?: '')),
                $c->getEmail() ?: '',
                $c->getPhoneProfessional() ?: '',
                $c->getPhonePersonal() ?: '',
                $c->isActive() ? 'Oui' : 'Non',
                implode('|', $profiles),
                $c->getCjm() ?: '',
                $c->getTjm() ?: '',
            ];
        }

        // Génération CSV sécurisée
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $r) {
            fputcsv($handle, $r);
        }
        rewind($handle);
        $csv = "\xEF\xBB\xBF".stream_get_contents($handle);

        $filename = sprintf('contributeurs_%s.csv', date('Y-m-d'));
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
