<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\EmploymentPeriod;
use App\Entity\Profile;
use App\Repository\ContributorRepository;
use App\Repository\EmploymentPeriodRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/employment-periods')]
#[IsGranted('ROLE_MANAGER')]
class EmploymentPeriodController extends AbstractController
{
    #[Route('', name: 'employment_period_index', methods: ['GET'])]
    public function index(Request $request, EmploymentPeriodRepository $employmentPeriodRepository, ContributorRepository $contributorRepository): Response
    {
        $contributorId = $request->query->get('contributor');

        $periods      = $employmentPeriodRepository->findWithOptionalContributorFilter($contributorId);
        $contributors = $contributorRepository->findActiveContributors();

        return $this->render('employment_period/index.html.twig', [
            'periods'             => $periods,
            'contributors'        => $contributors,
            'selectedContributor' => $contributorId,
        ]);
    }

    #[Route('/new', name: 'employment_period_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, EmploymentPeriodRepository $employmentPeriodRepository, ContributorRepository $contributorRepository): Response
    {
        $period = new EmploymentPeriod();

        // Pré-sélectionner le contributeur si fourni dans l'URL
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
            $period->setSalary($salary !== '' ? (float) $salary : null);

            $cjm = $request->request->get('cjm');
            $period->setCjm($cjm !== '' ? (float) $cjm : null);

            $tjm = $request->request->get('tjm');
            $period->setTjm($tjm !== '' ? (float) $tjm : null);

            $weeklyHours = $request->request->get('weekly_hours');
            $period->setWeeklyHours($weeklyHours !== '' ? (float) $weeklyHours : 35.0);

            $workTimePercentage = $request->request->get('work_time_percentage');
            $period->setWorkTimePercentage($workTimePercentage !== '' ? (float) $workTimePercentage : 100.0);

            // Gestion des profils
            $profileIds = $request->request->all('profiles');
            if (!empty($profileIds)) {
                foreach ($profileIds as $profileId) {
                    $profile = $em->getRepository(Profile::class)->find($profileId);
                    if ($profile) {
                        $period->addProfile($profile);
                    }
                }
            }

            $period->setNotes($request->request->get('notes'));

            // Vérifier les chevauchements de périodes
            if ($employmentPeriodRepository->hasOverlappingPeriods($period)) {
                $this->addFlash('error', 'Cette période chevauche avec une période existante pour ce contributeur.');

                $contributors = $contributorRepository->findActiveContributors();
                $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

                return $this->render('employment_period/new.html.twig', [
                    'period'       => $period,
                    'contributors' => $contributors,
                    'profiles'     => $profiles,
                ]);
            }

            $em->persist($period);
            $em->flush();

            $this->addFlash('success', 'Période d\'emploi créée avec succès');

            return $this->redirectToRoute('employment_period_show', ['id' => $period->getId()]);
        }

        $contributors = $contributorRepository->findActiveContributors();
        $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

        return $this->render('employment_period/new.html.twig', [
            'period'       => $period,
            'contributors' => $contributors,
            'profiles'     => $profiles,
        ]);
    }

    #[Route('/{id}', name: 'employment_period_show', methods: ['GET'])]
    public function show(EmploymentPeriod $period, EmploymentPeriodRepository $employmentPeriodRepository): Response
    {
        // Calculer la durée en jours
        $duration = null;
        if ($period->getStartDate()) {
            $endDate  = $period->getEndDate() ?? new DateTime();
            $duration = $period->getStartDate()->diff($endDate)->days + 1;
        }

        // Calculer le coût total sur la période
        $totalCost = $employmentPeriodRepository->calculatePeriodCost($period);

        return $this->render('employment_period/show.html.twig', [
            'period'    => $period,
            'duration'  => $duration,
            'totalCost' => $totalCost,
        ]);
    }

    #[Route('/{id}/edit', name: 'employment_period_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EmploymentPeriod $period, EntityManagerInterface $em, EmploymentPeriodRepository $employmentPeriodRepository, ContributorRepository $contributorRepository): Response
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
            $period->setSalary($salary !== '' ? (float) $salary : null);

            $cjm = $request->request->get('cjm');
            $period->setCjm($cjm !== '' ? (float) $cjm : null);

            $tjm = $request->request->get('tjm');
            $period->setTjm($tjm !== '' ? (float) $tjm : null);

            $weeklyHours = $request->request->get('weekly_hours');
            $period->setWeeklyHours($weeklyHours !== '' ? (float) $weeklyHours : 35.0);

            $workTimePercentage = $request->request->get('work_time_percentage');
            $period->setWorkTimePercentage($workTimePercentage !== '' ? (float) $workTimePercentage : 100.0);

            // Gestion des profils
            $period->getProfiles()->clear();
            $profileIds = $request->request->all('profiles');
            if (!empty($profileIds)) {
                foreach ($profileIds as $profileId) {
                    $profile = $em->getRepository(Profile::class)->find($profileId);
                    if ($profile) {
                        $period->addProfile($profile);
                    }
                }
            }

            $period->setNotes($request->request->get('notes'));

            // Vérifier les chevauchements de périodes (en excluant la période actuelle)
            if ($employmentPeriodRepository->hasOverlappingPeriods($period, $period->getId())) {
                $this->addFlash('error', 'Cette période chevauche avec une période existante pour ce contributeur.');

                $contributors = $contributorRepository->findActiveContributors();
                $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

                return $this->render('employment_period/edit.html.twig', [
                    'period'       => $period,
                    'contributors' => $contributors,
                    'profiles'     => $profiles,
                ]);
            }

            $em->flush();

            $this->addFlash('success', 'Période d\'emploi modifiée avec succès');

            return $this->redirectToRoute('employment_period_show', ['id' => $period->getId()]);
        }

        $contributors = $contributorRepository->findActiveContributors();
        $profiles     = $em->getRepository(Profile::class)->findBy(['active' => true], ['name' => 'ASC']);

        return $this->render('employment_period/edit.html.twig', [
            'period'       => $period,
            'contributors' => $contributors,
            'profiles'     => $profiles,
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
