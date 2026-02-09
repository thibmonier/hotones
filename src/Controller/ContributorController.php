<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\Pagination;
use App\Entity\Contributor;
use App\Form\ContributorType;
use App\Repository\ContributorRepository;
use App\Security\CompanyContext;
use App\Service\SecureFileUploadService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/contributors')]
#[IsGranted('ROLE_CHEF_PROJET')]
class ContributorController extends AbstractController
{
    public function __construct(
        private readonly ContributorRepository $contributorRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SecureFileUploadService $uploadService,
        private readonly LoggerInterface $logger,
        private readonly CompanyContext $companyContext,
    ) {
    }

    #[Route('', name: 'contributor_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);
        if ($reset) {
            $session->remove('contributor_filters');

            return $this->redirectToRoute('contributor_index');
        }

        $queryAll  = $request->query->all();
        $keys      = ['search', 'active', 'employment_status'];
        $hasFilter = count(array_intersect(array_keys($queryAll), $keys)) > 0;
        $saved     = $session->has('contributor_filters') ? (array) $session->get('contributor_filters') : [];

        $search           = $hasFilter ? $request->query->get('search', '') : $saved['search']    ?? '';
        $active           = $hasFilter ? $request->query->get('active', 'all') : $saved['active'] ?? 'all';
        $employmentStatus = $hasFilter
            ? $request->query->get('employment_status', 'all')
            : $saved['employment_status']                                          ?? 'all';
        $sort = $hasFilter ? $request->query->get('sort', 'name') : $saved['sort'] ?? 'name';
        $dir  = $hasFilter ? $request->query->get('dir', 'ASC') : $saved['dir']    ?? 'ASC';

        $qb        = $this->contributorRepository->buildFilteredQuery($search, $active, $employmentStatus, $sort, $dir);
        $direction = strtoupper((string) $dir) === 'DESC' ? 'DESC' : 'ASC';

        // Pagination
        $allowedPerPage = [10, 20, 50, 100];
        $perPageParam   = (int) $request->query->get('per_page', $saved['per_page'] ?? 20);
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 20;
        $page           = max(1, (int) $request->query->get('page', 1));
        $offset         = ($page - 1) * $perPage;

        $countQb = clone $qb;
        $total   = (int) $countQb
            ->select('COUNT(DISTINCT c.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        // Stats globales (sur l'ensemble filtré)
        $withUserQb = clone $qb;
        $withUser   = (int) $withUserQb
            ->select('COUNT(DISTINCT c.id)')
            ->resetDQLPart('orderBy')
            ->andWhere('c.user IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        $qb->setFirstResult($offset)->setMaxResults($perPage);
        $contributors = $qb->getQuery()->getResult();

        // Calculer les moyennes CJM/TJM à partir des périodes d'emploi des collaborateurs
        $allContributorsQb = clone $qb;
        $allContributorsQb->setFirstResult(0)->setMaxResults(null);
        $allContributors = $allContributorsQb->getQuery()->getResult();

        $cjmValues = [];
        $tjmValues = [];
        foreach ($allContributors as $contributor) {
            $cjm = $contributor->cjm;
            $tjm = $contributor->tjm;
            if ($cjm !== null) {
                $cjmValues[] = (float) $cjm;
            }
            if ($tjm !== null) {
                $tjmValues[] = (float) $tjm;
            }
        }

        $avgCjm     = !empty($cjmValues) ? array_sum($cjmValues) / count($cjmValues) : null;
        $avgTjm     = !empty($tjmValues) ? array_sum($tjmValues) / count($tjmValues) : null;
        $pagination = Pagination::create($page, $perPage, $total);

        $session->set('contributor_filters', [
            'search'            => $search,
            'active'            => $active,
            'employment_status' => $employmentStatus,
            'sort'              => $sort,
            'dir'               => $direction,
            'per_page'          => $perPage,
        ]);

        return $this->render('contributor/index.html.twig', [
            'contributors'       => $contributors,
            'search'             => $search,
            'active'             => $active,
            'employment_status'  => $employmentStatus,
            'sort'               => $sort,
            'dir'                => $direction,
            'pagination'         => $pagination->toArray(),
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
        $contributor->setCompany($this->companyContext->getCurrentCompany());
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                // Handle avatar upload
                /** @var UploadedFile|null $avatarFile */
                $avatarFile = $form->get('avatarFile')->getData();
                if ($avatarFile) {
                    $contributor->avatarFilename = $this->handleAvatarUpload($avatarFile);
                }

                $this->entityManager->persist($contributor);
                $this->entityManager->flush();

                $this->addFlash('success', 'Le collaborateur a été créé avec succès.');

                return $this->redirectToRoute('contributor_show', ['id' => $contributor->id]);
            } catch (RuntimeException $e) {
                $this->addFlash('error', $e->getMessage());
            }
        }

        return $this->render('contributor/new.html.twig', [
            'contributor' => $contributor,
            'form'        => $form,
        ]);
    }

    #[Route('/{id}', name: 'contributor_show', methods: ['GET'])]
    public function show(Contributor $contributor, EntityManagerInterface $em): Response
    {
        // Agréger les ressources disponibles par compétence et niveau
        $skillsData = $em
            ->createQueryBuilder()
            ->select('s.id', 's.name', 'cs.managerAssessmentLevel as level', 'COUNT(cs.id) as count')
            ->from(\App\Entity\ContributorSkill::class, 'cs')
            ->join('cs.skill', 's')
            ->join('cs.contributor', 'c')
            ->where('c.active = true')
            ->andWhere('cs.managerAssessmentLevel IS NOT NULL')
            ->groupBy('s.id', 's.name', 'cs.managerAssessmentLevel')
            ->orderBy('s.name', 'ASC')
            ->addOrderBy('cs.managerAssessmentLevel', 'DESC')
            ->getQuery()
            ->getResult();

        // Regrouper les données par compétence
        $skillsByName = [];
        foreach ($skillsData as $row) {
            $skillName = $row['name'];
            if (!isset($skillsByName[$skillName])) {
                $skillsByName[$skillName] = [
                    'name'   => $skillName,
                    'levels' => [1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0],
                ];
            }
            $skillsByName[$skillName]['levels'][$row['level']] = (int) $row['count'];
        }

        return $this->render('contributor/show.html.twig', [
            'contributor'     => $contributor,
            'skillsResources' => array_values($skillsByName),
        ]);
    }

    #[Route('/{id}/edit', name: 'contributor_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function edit(Request $request, Contributor $contributor): Response
    {
        $form = $this->createForm(ContributorType::class, $contributor);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->logger->info('Début édition collaborateur', [
                    'contributor_id' => $contributor->id,
                    'has_avatar'     => $form->get('avatarFile')->getData() !== null,
                ]);

                // Handle avatar upload
                /** @var UploadedFile $avatarFile */
                $avatarFile = $form->get('avatarFile')->getData();
                if ($avatarFile) {
                    $filename                    = $this->handleAvatarUpload($avatarFile);
                    $contributor->avatarFilename = $filename;
                    $this->logger->info('Avatar uploadé', ['filename' => $filename]);
                }

                $this->entityManager->flush();

                $this->logger->info('Collaborateur modifié avec succès');
                $this->addFlash('success', 'Le collaborateur a été modifié avec succès.');

                return $this->redirectToRoute('contributor_show', ['id' => $contributor->id]);
            } catch (RuntimeException $e) {
                $this->logger->error('Erreur modification collaborateur', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', $e->getMessage());
            } catch (Exception $e) {
                $this->logger->error('Erreur inattendue modification collaborateur', [
                    'message' => $e->getMessage(),
                    'trace'   => $e->getTraceAsString(),
                ]);
                $this->addFlash('error', 'Une erreur inattendue s\'est produite. Veuillez réessayer.');
            }
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
        $periods = $this->entityManager->getRepository(\App\Entity\EmploymentPeriod::class)->findBy([
            'contributor' => $contributor,
        ], ['startDate' => 'DESC']);

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
        if ($this->isCsrfTokenValid('delete'.$contributor->id, $request->request->get('_token'))) {
            // Soft delete - marquer comme inactif au lieu de supprimer
            $contributor->active = false;
            $this->entityManager->flush();
            $this->addFlash('success', 'Collaborateur désactivé avec succès');
        }

        return $this->redirectToRoute('contributor_index');
    }

    private function handleAvatarUpload(UploadedFile $file): string
    {
        try {
            $this->logger->info('handleAvatarUpload appelé', [
                'filename' => $file->getClientOriginalName(),
                'size'     => $file->getSize(),
                'mime'     => $file->getMimeType(),
            ]);

            $result = $this->uploadService->uploadImage($file, 'avatars');

            $this->logger->info('handleAvatarUpload réussi', ['result' => $result]);

            return $result;
        } catch (Exception $e) {
            $this->logger->error('handleAvatarUpload échoué', [
                'message'  => $e->getMessage(),
                'previous' => $e->getPrevious()?->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);
            throw new RuntimeException(sprintf('Erreur lors de l\'upload de l\'avatar: %s', $e->getMessage()), 0, $e);
        }
    }

    #[Route('/export.csv', name: 'contributor_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $session          = $request->getSession();
        $saved            = $session->has('contributor_filters') ? (array) $session->get('contributor_filters') : [];
        $search           = $request->query->get('search', $saved['search'] ?? '');
        $active           = $request->query->get('active', $saved['active'] ?? 'all');
        $employmentStatus = $request->query->get('employment_status', $saved['employment_status'] ?? 'all');
        $sort             = $request->query->get('sort', $saved['sort'] ?? 'name');
        $dir              = $request->query->get('dir', $saved['dir'] ?? 'ASC');

        $qb           = $this->contributorRepository->buildFilteredQuery($search, $active, $employmentStatus, $sort, $dir);
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
                $c->getName() ?: trim(($c->firstName ?: '').' '.($c->lastName ?: '')),
                $c->email ?: '',
                $c->phoneProfessional ?: '',
                $c->phonePersonal ?: '',
                $c->active ? 'Oui' : 'Non',
                implode('|', $profiles),
                $c->cjm ?: '',
                $c->tjm ?: '',
            ];
        }

        // Génération CSV sécurisée
        $handle = fopen('php://temp', 'r+');
        foreach ($rows as $r) {
            fputcsv($handle, $r, escape: '\\');
        }
        rewind($handle);
        $csv = "\xEF\xBB\xBF".stream_get_contents($handle);

        $filename = sprintf('collaborateurs_%s.csv', date('Y-m-d'));
        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }
}
