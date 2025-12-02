<?php

namespace App\Controller;

use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Entity\Vacation;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    #[IsGranted('ROLE_USER')]
    public function index(EntityManagerInterface $em): Response
    {
        $currentMonth = new DateTime('first day of this month');
        $endMonth     = new DateTime('last day of this month');

        $projectRepo     = $em->getRepository(Project::class);
        $contributorRepo = $em->getRepository(Contributor::class);
        $timesheetRepo   = $em->getRepository(Timesheet::class);

        // KPIs globaux via repositories
        $totalProjects     = $projectRepo->count([]);
        $activeProjects    = $projectRepo->countActiveProjects();
        $totalContributors = $contributorRepo->countActiveContributors();

        // CA total de tous les projets (une seule requête SQL)
        $totalRevenue = $projectRepo->getTotalRevenue();

        // Temps saisies ce mois-ci via repository
        $monthlyHours = $timesheetRepo->getTotalHoursForMonth($currentMonth, $endMonth);

        // Projets récents via repository
        $recentProjects = $projectRepo->findRecentProjects(5);

        // Mes temps récents (si contributeur) via repository
        $contributor        = $contributorRepo->findByUser($this->getUser());
        $myRecentTimesheets = [];
        if ($contributor) {
            $myRecentTimesheets = $timesheetRepo->findRecentByContributor($contributor, 5);
        }

        // Projets par statut via repository
        $projectsByStatus = $projectRepo->getProjectsByStatus();

        // Demandes de congés en attente pour les managers (requête unique pour tous les contributeurs)
        $pendingVacations = [];
        if ($this->isGranted('ROLE_MANAGER') && $contributor) {
            $vacationRepo        = $em->getRepository(Vacation::class);
            $managedContributors = $contributor->getManagedContributors();

            // Une seule requête pour toutes les vacations en attente
            $pendingVacations = $vacationRepo->findPendingForContributors($managedContributors->toArray());
        }

        return $this->render('home/index.html.twig', [
            'totalProjects'      => $totalProjects,
            'activeProjects'     => $activeProjects,
            'totalContributors'  => $totalContributors,
            'totalRevenue'       => $totalRevenue,
            'monthlyHours'       => $monthlyHours,
            'recentProjects'     => $recentProjects,
            'myRecentTimesheets' => $myRecentTimesheets,
            'contributor'        => $contributor,
            'projectsByStatus'   => $projectsByStatus,
            'pendingVacations'   => $pendingVacations,
        ]);
    }
}
