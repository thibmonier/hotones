<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Profile;
use App\Repository\ContributorRepository;
use App\Repository\EmploymentPeriodRepository;
use App\Security\CompanyContext;
use App\Service\CjmCalculatorService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employment-periods')]
#[IsGranted('ROLE_MANAGER')]
class EmploymentPeriodController extends AbstractController
{
    public function __construct(
        private readonly CompanyContext $companyContext
    ) {
    }

    #[Route('', name: 'employment_period_index', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, PaginatorInterface $paginator, ContributorRepository $contributorRepository): Response
    {
        $session = $request->getSession();
        $reset   = (bool) $request->query->get('reset', false);
        if ($reset) {
            $session->remove('employment_period_filters');

            return $this->redirectToRoute('employment_period_index');
        }

        // Charger filtres depuis la session si aucun filtre explicite n'est fourni
        $queryAll   = $request->query->all();
        $filterKeys = ['contributor', 'status', 'per_page', 'sort', 'dir'];
        $hasFilter  = (bool) count(array_intersect(array_keys($queryAll), $filterKeys));
        $saved      = $session->has('employment_period_filters') ? (array) $session->get('employment_period_filters') : [];

        // Filtres
        $contributorId = $hasFilter ? ($request->query->get('contributor') ?: '') : ($saved['contributor'] ?? '');
        $status        = $hasFilter ? ($request->query->get('status') ?: '') : ($saved['status'] ?? '');

        // Tri
        $sort = $hasFilter ? ($request->query->get('sort') ?: ($saved['sort'] ?? 'startDate')) : ($saved['sort'] ?? 'startDate');
        $dir  = $hasFilter ? ($request->query->get('dir') ?: ($saved['dir'] ?? 'DESC')) : ($saved['dir'] ?? 'DESC');

        // Pagination
        $allowedPerPage = [10, 25, 50, 100];
        $perPageParam   = (int) ($hasFilter ? ($request->query->get('per_page', 25)) : ($saved['per_page'] ?? 25));
        $perPage        = in_array($perPageParam, $allowedPerPage, true) ? $perPageParam : 25;

        // Sauvegarder en session
        $session->set('employment_period_filters', [
            'contributor' => $contributorId,
            'status'      => $status,
            'per_page'    => $perPage,
            'sort'        => $sort,
            'dir'         => $dir,
        ]);

        // Query builder avec filtres
        $qb = $em->getRepository(EmploymentPeriod::class)->createQueryBuilder('ep')
            ->leftJoin('ep.contributor', 'c')
            ->addSelect('c');

        if ($contributorId) {
            $qb->andWhere('ep.contributor = :contributor')
                ->setParameter('contributor', $contributorId);
        }

        if ($status === 'active') {
            $qb->andWhere('ep.endDate IS NULL OR ep.endDate >= :today')
                ->setParameter('today', new DateTime());
        } elseif ($status === 'ended') {
            $qb->andWhere('ep.endDate < :today')
                ->setParameter('today', new DateTime());
        }

        // Tri
        $validSortFields = [
            'contributor' => 'c.lastName',
            'startDate'   => 'ep.startDate',
            'salary'      => 'ep.salary',
            'cjm'         => 'ep.cjm',
            'tjm'         => 'ep.tjm',
        ];
        $sortField = $validSortFields[$sort] ?? 'ep.startDate';
        $sortDir   = strtoupper((string) $dir) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy($sortField, $sortDir);

        // Pagination
        $pagination = $paginator->paginate(
            $qb->getQuery(),
            $request->query->getInt('page', 1),
            $perPage,
        );

        $contributors = $contributorRepository->findActiveContributors();

        return $this->render('employment_period/index.html.twig', [
            'periods'      => $pagination,
            'contributors' => $contributors,
            'filters'      => [
                'contributor' => $contributorId,
                'status'      => $status,
            ],
            'sort' => $sort,
            'dir'  => $dir,
        ]);
    }

    #[Route('/export', name: 'employment_period_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request, EntityManagerInterface $em): Response
    {
        // Mêmes filtres que l'index
        $contributorId = $request->query->get('contributor', '');
        $status        = $request->query->get('status', '');

        $qb = $em->getRepository(EmploymentPeriod::class)->createQueryBuilder('ep')
            ->leftJoin('ep.contributor', 'c')
            ->addSelect('c')
            ->orderBy('ep.startDate', 'DESC');

        if ($contributorId) {
            $qb->andWhere('ep.contributor = :contributor')
                ->setParameter('contributor', $contributorId);
        }

        if ($status === 'active') {
            $qb->andWhere('ep.endDate IS NULL OR ep.endDate >= :today')
                ->setParameter('today', new DateTime());
        } elseif ($status === 'ended') {
            $qb->andWhere('ep.endDate < :today')
                ->setParameter('today', new DateTime());
        }

        $periods = $qb->getQuery()->getResult();

        // Génération CSV
        $csv = "Collaborateur;Date début;Date fin;Profils;Salaire;CJM;TJM;Heures/semaine;Temps de travail;Statut\n";
        foreach ($periods as $period) {
            $profiles = [];
            foreach ($period->profiles as $profile) {
                $profiles[] = $profile->getName();
            }

            $csv .= sprintf(
                "%s;%s;%s;%s;%s;%s;%s;%s;%s;%s\n",
                $period->contributor->getName(),
                $period->startDate->format('d/m/Y'),
                $period->endDate ? $period->endDate->format('d/m/Y') : 'En cours',
                implode(', ', $profiles),
                $period->salary ? number_format($period->salary, 0, ',', ' ').' €' : '',
                $period->cjm ? number_format($period->cjm, 0, ',', ' ').' €' : '',
                $period->tjm ? number_format($period->tjm, 0, ',', ' ').' €' : '',
                $period->weeklyHours.'h',
                $period->workTimePercentage.'%',
                $period->endDate === null || $period->endDate >= new DateTime() ? 'Actif' : 'Terminé',
            );
        }

        $response = new Response($csv);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            'periodes_emploi_'.date('Y-m-d').'.csv',
        ));

        return $response;
    }

    #[Route('/new', name: 'employment_period_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, EmploymentPeriodRepository $employmentPeriodRepository, ContributorRepository $contributorRepository, CjmCalculatorService $cjmCalculatorService): Response
    {
        $company = $this->companyContext->getCurrentCompany();
        $period  = new EmploymentPeriod();
        $period->setCompany($company);

        // Pré-sélectionner le collaborateur si fourni dans l'URL
        if ($contributorId = $request->query->get('contributor')) {
            $contributor = $em->getRepository(Contributor::class)->find($contributorId);
            if ($contributor) {
                $period->setContributor($contributor);
            }
        }

        if ($request->isMethod('POST')) {
            // Contributeur
            if ($contributorId = $request->request->get('contributor_id')) {
                $contributor = $em->getRepository(Contributor::class)->find($contributorId);
                if ($contributor) {
                    $period->setContributor($contributor);
                }
            }

            if ($request->request->get('start_date')) {
                $period->setStartDate(new DateTime($request->request->get('start_date')));
            }

            if ($request->request->get('end_date')) {
                $period->setEndDate(new DateTime($request->request->get('end_date')));
            }

            // Données financières
            $salary = $request->request->get('salary');
            $period->setSalary($salary !== '' && $salary !== null ? (string) $salary : null);

            // Calculer automatiquement le CJM si un salaire est fourni
            if ($period->getSalary()) {
                $year          = (int) $period->getStartDate()->format('Y');
                $calculatedCjm = $cjmCalculatorService->calculateCjmFromMonthlySalary($period->getSalary(), $year);
                $period->setCjm((string) $calculatedCjm);
            } else {
                // Autoriser la saisie manuelle si pas de salaire
                $cjm = $request->request->get('cjm');
                $period->setCjm($cjm !== '' && $cjm !== null ? (string) $cjm : null);
            }

            $tjm = $request->request->get('tjm');
            $period->setTjm($tjm !== '' && $tjm !== null ? (string) $tjm : null);

            $weeklyHours = $request->request->get('weekly_hours');
            $period->setWeeklyHours($weeklyHours !== '' && $weeklyHours !== null ? (string) $weeklyHours : '35.00');

            $workTimePercentage = $request->request->get('work_time_percentage');
            $period->setWorkTimePercentage($workTimePercentage !== '' && $workTimePercentage !== null ? (string) $workTimePercentage : '100.00');

            // Gestion des profils
            $profileIds = $request->request->all('profiles');
            foreach ($profileIds as $profileId) {
                $profile = $em->getRepository(Profile::class)->find($profileId);
                if ($profile) {
                    $period->addProfile($profile);
                }
            }

            $period->setNotes($request->request->get('notes'));

            // Vérifier les chevauchements de périodes
            if ($employmentPeriodRepository->hasOverlappingPeriods($period)) {
                $this->addFlash('error', 'Cette période chevauche avec une période existante pour ce collaborateur.');

                $contributors = $contributorRepository->findActiveContributors();
                $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

                // Obtenir les informations de calcul CJM
                $year              = (int) $period->getStartDate()->format('Y');
                $calculationReport = $cjmCalculatorService->getCalculationReport($year);

                return $this->render('employment_period/new.html.twig', [
                    'period'                => $period,
                    'contributors'          => $contributors,
                    'profiles'              => $profiles,
                    'selectedContributorId' => $period->contributor ? $period->contributor->getId() : null,
                    'calculationReport'     => $calculationReport,
                ]);
            }

            $em->persist($period);
            $em->flush();

            $this->addFlash('success', 'Période d\'emploi créée avec succès');

            return $this->redirectToRoute('employment_period_show', ['id' => $period->getId()]);
        }

        $contributors = $contributorRepository->findActiveContributors();
        $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

        // Obtenir les informations de calcul CJM pour l'année en cours
        $year              = (int) date('Y');
        $calculationReport = $cjmCalculatorService->getCalculationReport($year);

        return $this->render('employment_period/new.html.twig', [
            'period'                => $period,
            'contributors'          => $contributors,
            'profiles'              => $profiles,
            'selectedContributorId' => $period->contributor ? $period->contributor->getId() : null,
            'calculationReport'     => $calculationReport,
        ]);
    }

    #[Route('/{id}', name: 'employment_period_show', methods: ['GET'])]
    public function show(EmploymentPeriod $period, EmploymentPeriodRepository $employmentPeriodRepository): Response
    {
        // Calculer la durée en jours
        $endDate  = $period->endDate ?? new DateTime();
        $duration = $period->startDate->diff($endDate)->days + 1;

        // Calculer le coût total sur la période
        $totalCost = $employmentPeriodRepository->calculatePeriodCost($period);

        return $this->render('employment_period/show.html.twig', [
            'period'    => $period,
            'duration'  => $duration,
            'totalCost' => $totalCost,
        ]);
    }

    #[Route('/{id}/edit', name: 'employment_period_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EmploymentPeriod $period, EntityManagerInterface $em, EmploymentPeriodRepository $employmentPeriodRepository, ContributorRepository $contributorRepository, CjmCalculatorService $cjmCalculatorService): Response
    {
        if ($request->isMethod('POST')) {
            // Contributeur
            if ($contributorId = $request->request->get('contributor_id')) {
                $contributor = $em->getRepository(Contributor::class)->find($contributorId);
                $period->setContributor($contributor);
            }

            if ($request->request->get('start_date')) {
                $period->setStartDate(new DateTime($request->request->get('start_date')));
            }

            if ($request->request->get('end_date')) {
                $period->setEndDate(new DateTime($request->request->get('end_date')));
            } else {
                $period->setEndDate(null);
            }

            // Données financières
            $salary = $request->request->get('salary');
            $period->setSalary($salary !== '' && $salary !== null ? (string) $salary : null);

            // Calculer automatiquement le CJM si un salaire est fourni
            if ($period->getSalary()) {
                $year          = (int) $period->getStartDate()->format('Y');
                $calculatedCjm = $cjmCalculatorService->calculateCjmFromMonthlySalary($period->getSalary(), $year);
                $period->setCjm((string) $calculatedCjm);
            } else {
                // Autoriser la saisie manuelle si pas de salaire
                $cjm = $request->request->get('cjm');
                $period->setCjm($cjm !== '' && $cjm !== null ? (string) $cjm : null);
            }

            $tjm = $request->request->get('tjm');
            $period->setTjm($tjm !== '' && $tjm !== null ? (string) $tjm : null);

            $weeklyHours = $request->request->get('weekly_hours');
            $period->setWeeklyHours($weeklyHours !== '' && $weeklyHours !== null ? (string) $weeklyHours : '35.00');

            $workTimePercentage = $request->request->get('work_time_percentage');
            $period->setWorkTimePercentage($workTimePercentage !== '' && $workTimePercentage !== null ? (string) $workTimePercentage : '100.00');

            // Gestion des profils
            $period->profiles->clear();
            $profileIds = $request->request->all('profiles');
            foreach ($profileIds as $profileId) {
                $profile = $em->getRepository(Profile::class)->find($profileId);
                if ($profile) {
                    $period->addProfile($profile);
                }
            }

            $period->setNotes($request->request->get('notes'));

            // Vérifier les chevauchements de périodes (en excluant la période actuelle)
            if ($employmentPeriodRepository->hasOverlappingPeriods($period, $period->getId())) {
                $this->addFlash('error', 'Cette période chevauche avec une période existante pour ce collaborateur.');

                $contributors = $contributorRepository->findActiveContributors();
                $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

                // Obtenir les informations de calcul CJM
                $year              = (int) $period->getStartDate()->format('Y');
                $calculationReport = $cjmCalculatorService->getCalculationReport($year);

                return $this->render('employment_period/edit.html.twig', [
                    'period'            => $period,
                    'contributors'      => $contributors,
                    'profiles'          => $profiles,
                    'calculationReport' => $calculationReport,
                ]);
            }

            $em->flush();

            $this->addFlash('success', 'Période d\'emploi modifiée avec succès');

            return $this->redirectToRoute('employment_period_show', ['id' => $period->getId()]);
        }

        $contributors = $contributorRepository->findActiveContributors();
        $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

        // Obtenir les informations de calcul CJM pour l'année de la période
        $year              = (int) $period->getStartDate()->format('Y');
        $calculationReport = $cjmCalculatorService->getCalculationReport($year);

        return $this->render('employment_period/edit.html.twig', [
            'period'            => $period,
            'contributors'      => $contributors,
            'profiles'          => $profiles,
            'calculationReport' => $calculationReport,
        ]);
    }

    #[Route('/{id}/delete', name: 'employment_period_delete', methods: ['POST'])]
    public function delete(Request $request, EmploymentPeriod $period, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete'.$period->getId(), $request->request->get('_token'))) {
            $em->remove($period);
            $em->flush();
            $this->addFlash('success', 'Période d\'emploi supprimée avec succès');
        }

        return $this->redirectToRoute('employment_period_index');
    }
}
