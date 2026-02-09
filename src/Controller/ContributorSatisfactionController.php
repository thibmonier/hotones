<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\ContributorSatisfaction;
use App\Form\ContributorSatisfactionType;
use App\Repository\ContributorRepository;
use App\Repository\ContributorSatisfactionRepository;
use App\Security\CompanyContext;
use App\Service\GamificationService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/satisfaction')]
#[IsGranted('ROLE_USER')]
class ContributorSatisfactionController extends AbstractController
{
    public function __construct(
        private readonly ContributorSatisfactionRepository $satisfactionRepository,
        private readonly ContributorRepository $contributorRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly GamificationService $gamificationService,
        private readonly CompanyContext $companyContext,
    ) {
    }

    /**
     * Page d'accueil de la satisfaction collaborateur.
     * Redirige vers la saisie du mois en cours ou affiche l'historique.
     */
    #[Route('', name: 'satisfaction_index', methods: ['GET'])]
    public function index(): Response
    {
        $user        = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$contributor) {
            $this->addFlash('error', 'Aucun profil collaborateur associé à votre compte');

            return $this->redirectToRoute('dashboard');
        }

        // Récupérer l'historique des satisfactions
        $satisfactions = $this->satisfactionRepository->findByContributor($contributor);

        // Mois en cours
        $now          = new DateTime();
        $currentYear  = (int) $now->format('Y');
        $currentMonth = (int) $now->format('n');

        // Vérifier si le mois en cours a déjà été saisi
        $currentSatisfaction = $this->satisfactionRepository->findByContributorAndPeriod(
            $contributor,
            $currentYear,
            $currentMonth,
        );

        return $this->render('satisfaction/index.html.twig', [
            'contributor'          => $contributor,
            'satisfactions'        => $satisfactions,
            'current_satisfaction' => $currentSatisfaction,
            'current_year'         => $currentYear,
            'current_month'        => $currentMonth,
        ]);
    }

    /**
     * Saisie de la satisfaction pour une période donnée.
     */
    #[Route('/submit/{year}/{month}', name: 'satisfaction_submit', methods: ['GET', 'POST'])]
    public function submit(int $year, int $month, Request $request): Response
    {
        $user        = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        if (!$contributor) {
            $this->addFlash('error', 'Aucun profil collaborateur associé à votre compte');

            return $this->redirectToRoute('dashboard');
        }

        // Vérifier si une satisfaction existe déjà pour cette période
        $satisfaction = $this->satisfactionRepository->findByContributorAndPeriod($contributor, $year, $month);

        if (!$satisfaction) {
            $satisfaction = new ContributorSatisfaction();
            $satisfaction->setCompany($this->companyContext->getCurrentCompany());
            $satisfaction->setContributor($contributor);
            $satisfaction->setYear($year);
            $satisfaction->setMonth($month);
        }

        $form = $this->createForm(ContributorSatisfactionType::class, $satisfaction);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $isNew = $satisfaction->getId() === null;

            $this->entityManager->persist($satisfaction);
            $this->entityManager->flush();

            // Ajouter de l'XP uniquement pour les nouvelles saisies
            if ($isNew) {
                try {
                    $xpResult = $this->gamificationService->addXp(
                        $contributor,
                        50,
                        'satisfaction',
                        sprintf('Saisie satisfaction %s/%s', $month, $year),
                    );

                    if ($xpResult['level_up']) {
                        $this->addFlash('success', sprintf(
                            'Félicitations ! Vous êtes passé au niveau %d !',
                            $xpResult['new_level'],
                        ));
                    }

                    if (!empty($xpResult['badges_unlocked'])) {
                        $badgeNames = array_map(fn ($badge): string => $badge->getName(), $xpResult['badges_unlocked']);
                        $this->addFlash('success', 'Nouveau badge débloqué : '.implode(', ', $badgeNames));
                    }
                } catch (Exception) {
                    // Ne pas bloquer la satisfaction si l'XP échoue
                }
            }

            $this->addFlash('success', 'Votre satisfaction a été enregistrée avec succès. Merci pour votre retour !');

            return $this->redirectToRoute('satisfaction_index');
        }

        return $this->render('satisfaction/submit.html.twig', [
            'form'         => $form->createView(),
            'satisfaction' => $satisfaction,
            'year'         => $year,
            'month'        => $month,
            'is_edit'      => $satisfaction->getId() !== null,
        ]);
    }

    /**
     * Dashboard de statistiques pour les managers.
     */
    #[Route('/stats', name: 'satisfaction_stats', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function stats(Request $request): Response
    {
        $year  = (int) $request->query->get('year', date('Y'));
        $month = $request->query->get('month') ? (int) $request->query->get('month') : null;

        if ($month) {
            // Stats pour un mois spécifique
            $stats         = $this->satisfactionRepository->getStatsByPeriod($year, $month);
            $satisfactions = $this->satisfactionRepository->findByPeriod($year, $month);
            $title         = 'Satisfaction collaborateur - '.$this->getMonthLabel($month).' '.$year;
        } else {
            // Stats pour une année
            $stats         = $this->satisfactionRepository->getStatsByYear($year);
            $satisfactions = $this->satisfactionRepository->findByYear($year);
            $title         = 'Satisfaction collaborateur - Année '.$year;
        }

        // Nombre total de collaborateurs actifs
        $totalContributors = $this->contributorRepository->count(['active' => true]);
        $responseRate      = $totalContributors > 0 ? round(($stats['total'] / $totalContributors) * 100, 1) : 0;

        return $this->render('satisfaction/stats.html.twig', [
            'stats'              => $stats,
            'satisfactions'      => $satisfactions,
            'year'               => $year,
            'month'              => $month,
            'title'              => $title,
            'total_contributors' => $totalContributors,
            'response_rate'      => $responseRate,
        ]);
    }

    /**
     * Voir le détail d'une satisfaction.
     */
    #[Route('/{id}', name: 'satisfaction_show', methods: ['GET'])]
    public function show(ContributorSatisfaction $satisfaction): Response
    {
        $user        = $this->getUser();
        $contributor = $this->contributorRepository->findOneBy(['user' => $user]);

        // Vérifier que l'utilisateur a le droit de voir cette satisfaction
        // Soit c'est sa propre satisfaction, soit il est manager
        if (!$this->isGranted('ROLE_MANAGER') && $satisfaction->getContributor() !== $contributor) {
            throw $this->createAccessDeniedException('Vous n\'avez pas accès à cette satisfaction');
        }

        return $this->render('satisfaction/show.html.twig', [
            'satisfaction' => $satisfaction,
        ]);
    }

    /**
     * Supprimer une satisfaction (réservé aux managers).
     */
    #[Route('/{id}/delete', name: 'satisfaction_delete', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(ContributorSatisfaction $satisfaction, Request $request): Response
    {
        if (!$this->isCsrfTokenValid(
            'satisfaction_delete'.$satisfaction->getId(),
            (string) $request->request->get('_token'),
        )) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('satisfaction_stats');
        }

        $this->entityManager->remove($satisfaction);
        $this->entityManager->flush();

        $this->addFlash('success', 'La satisfaction a été supprimée avec succès');

        return $this->redirectToRoute('satisfaction_stats');
    }

    #[Route('/export/csv', name: 'satisfaction_export_csv', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function exportCsv(Request $request): Response
    {
        $year  = (int) $request->query->get('year', date('Y'));
        $month = $request->query->get('month') ? (int) $request->query->get('month') : null;

        if ($month) {
            $satisfactions = $this->satisfactionRepository->findByPeriod($year, $month);
            $filename      = sprintf('satisfaction_collaborateur_%s_%d.csv', $this->getMonthLabel($month), $year);
        } else {
            $satisfactions = $this->satisfactionRepository->findByYear($year);
            $filename      = sprintf('satisfaction_collaborateur_%d.csv', $year);
        }

        // Créer le contenu CSV
        $csv   = [];
        $csv[] = [
            'ID',
            'Collaborateur',
            'Année',
            'Mois',
            'Score global',
            'Score projets',
            'Score équipe',
            'Score environnement',
            'Score équilibre',
            'Points positifs',
            'Points d\'amélioration',
            'Commentaire',
            'Saisi le',
        ];

        foreach ($satisfactions as $satisfaction) {
            $csv[] = [
                $satisfaction->getId(),
                $satisfaction->getContributor()->getFullName(),
                $satisfaction->getYear(),
                $satisfaction->getMonthLabel(),
                $satisfaction->getOverallScore(),
                $satisfaction->getProjectsScore()        ?? '',
                $satisfaction->getTeamScore()            ?? '',
                $satisfaction->getWorkEnvironmentScore() ?? '',
                $satisfaction->getWorkLifeBalanceScore() ?? '',
                $satisfaction->getPositivePoints()       ?? '',
                $satisfaction->getImprovementPoints()    ?? '',
                $satisfaction->getComment()              ?? '',
                $satisfaction->getSubmittedAt()->format('Y-m-d H:i:s'),
            ];
        }

        // Générer le fichier CSV
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row, ';', escape: '\\');
        }
        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }

    private function getMonthLabel(int $month): string
    {
        return match ($month) {
            1       => 'Janvier',
            2       => 'Février',
            3       => 'Mars',
            4       => 'Avril',
            5       => 'Mai',
            6       => 'Juin',
            7       => 'Juillet',
            8       => 'Août',
            9       => 'Septembre',
            10      => 'Octobre',
            11      => 'Novembre',
            12      => 'Décembre',
            default => '',
        };
    }
}
