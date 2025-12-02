<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\NpsSurvey;
use App\Entity\Project;
use App\Form\NpsSurveyType;
use App\Repository\NpsSurveyRepository;
use App\Service\NpsMailerService;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/nps')]
#[IsGranted('ROLE_MANAGER')]
class NpsController extends AbstractController
{
    public function __construct(
        private readonly NpsSurveyRepository $npsSurveyRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly NpsMailerService $npsMailerService,
    ) {
    }

    #[Route('', name: 'nps_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $status    = $request->query->get('status', 'all');
        $projectId = $request->query->get('project');

        $qb = $this->npsSurveyRepository->createQueryBuilder('n')
            ->leftJoin('n.project', 'p')
            ->addSelect('p')
            ->orderBy('n.sentAt', 'DESC');

        if ($status !== 'all') {
            $qb->andWhere('n.status = :status')
               ->setParameter('status', $status);
        }

        if ($projectId) {
            $qb->andWhere('n.project = :project')
               ->setParameter('project', $projectId);
        }

        $surveys = $qb->getQuery()->getResult();

        // Statistiques globales
        $totalSurveys     = count($surveys);
        $completedSurveys = array_filter($surveys, fn ($s) => $s->getStatus() === NpsSurvey::STATUS_COMPLETED);
        $completedCount   = count($completedSurveys);
        $responseRate     = $totalSurveys > 0 ? round(($completedCount / $totalSurveys) * 100, 1) : 0;

        // Calculer le NPS moyen sur toutes les réponses
        $allCompleted = $this->npsSurveyRepository->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.score IS NOT NULL')
            ->setParameter('status', NpsSurvey::STATUS_COMPLETED)
            ->getQuery()
            ->getResult();

        $npsScore   = null;
        $promoters  = 0;
        $passives   = 0;
        $detractors = 0;

        if (!empty($allCompleted)) {
            $total = count($allCompleted);

            foreach ($allCompleted as $survey) {
                $category = $survey->getCategory();
                if ($category === 'promoter') {
                    ++$promoters;
                } elseif ($category === 'passive') {
                    ++$passives;
                } elseif ($category === 'detractor') {
                    ++$detractors;
                }
            }

            $npsScore = (($promoters / $total) - ($detractors / $total)) * 100;
        }

        return $this->render('nps/index.html.twig', [
            'surveys'          => $surveys,
            'status'           => $status,
            'project_id'       => $projectId,
            'total_surveys'    => $totalSurveys,
            'completed_count'  => $completedCount,
            'response_rate'    => $responseRate,
            'nps_score'        => $npsScore !== null ? round($npsScore, 1) : null,
            'promoters_count'  => $promoters,
            'passives_count'   => $passives,
            'detractors_count' => $detractors,
        ]);
    }

    #[Route('/new', name: 'nps_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $survey = new NpsSurvey();
        $form   = $this->createForm(NpsSurveyType::class, $survey);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->persist($survey);
            $this->entityManager->flush();

            // Envoyer l'email au client
            try {
                $this->npsMailerService->sendNpsSurvey($survey);
                $this->addFlash('success', 'L\'enquête NPS a été créée et envoyée avec succès au client.');
            } catch (Exception $e) {
                $this->addFlash('warning', 'L\'enquête a été créée mais l\'email n\'a pas pu être envoyé : '.$e->getMessage());
            }

            return $this->redirectToRoute('nps_show', ['id' => $survey->getId()]);
        }

        return $this->render('nps/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'nps_show', methods: ['GET'])]
    public function show(NpsSurvey $survey): Response
    {
        $project = $survey->getProject();

        // Récupérer toutes les enquêtes pour ce projet
        $projectSurveys = $this->npsSurveyRepository->findByProject($project);

        // Calculer les stats pour ce projet
        $stats = $this->npsSurveyRepository->getStatsByProject($project);

        return $this->render('nps/show.html.twig', [
            'survey'          => $survey,
            'project_surveys' => $projectSurveys,
            'stats'           => $stats,
        ]);
    }

    #[Route('/{id}/resend', name: 'nps_resend', methods: ['POST'])]
    public function resend(NpsSurvey $survey, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('nps_resend'.$survey->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('nps_show', ['id' => $survey->getId()]);
        }

        if ($survey->getStatus() === NpsSurvey::STATUS_COMPLETED) {
            $this->addFlash('error', 'Cette enquête a déjà été complétée, impossible de la renvoyer');

            return $this->redirectToRoute('nps_show', ['id' => $survey->getId()]);
        }

        // Renvoyer l'email de rappel
        try {
            $this->npsMailerService->sendNpsReminder($survey);
            $this->addFlash('success', 'L\'email de rappel a été envoyé avec succès');
        } catch (Exception $e) {
            $this->addFlash('error', 'L\'email n\'a pas pu être envoyé : '.$e->getMessage());
        }

        return $this->redirectToRoute('nps_show', ['id' => $survey->getId()]);
    }

    #[Route('/{id}/delete', name: 'nps_delete', methods: ['POST'])]
    public function delete(NpsSurvey $survey, Request $request): Response
    {
        if (!$this->isCsrfTokenValid('nps_delete'.$survey->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide');

            return $this->redirectToRoute('nps_index');
        }

        $this->entityManager->remove($survey);
        $this->entityManager->flush();

        $this->addFlash('success', 'L\'enquête a été supprimée avec succès');

        return $this->redirectToRoute('nps_index');
    }

    #[Route('/project/{id}', name: 'nps_project', methods: ['GET'])]
    public function project(Project $project): Response
    {
        $surveys = $this->npsSurveyRepository->findByProject($project);
        $stats   = $this->npsSurveyRepository->getStatsByProject($project);

        return $this->render('nps/project.html.twig', [
            'project' => $project,
            'surveys' => $surveys,
            'stats'   => $stats,
        ]);
    }

    #[Route('/export/csv', name: 'nps_export_csv', methods: ['GET'])]
    public function exportCsv(Request $request): Response
    {
        $status = $request->query->get('status', 'all');

        $qb = $this->npsSurveyRepository->createQueryBuilder('n')
            ->leftJoin('n.project', 'p')
            ->addSelect('p')
            ->orderBy('n.sentAt', 'DESC');

        if ($status !== 'all') {
            $qb->andWhere('n.status = :status')
               ->setParameter('status', $status);
        }

        $surveys = $qb->getQuery()->getResult();

        // Créer le contenu CSV
        $csv   = [];
        $csv[] = ['ID', 'Projet', 'Destinataire', 'Email', 'Envoyé le', 'Répondu le', 'Statut', 'Score', 'Catégorie', 'Commentaire', 'Expire le'];

        foreach ($surveys as $survey) {
            $csv[] = [
                $survey->getId(),
                $survey->getProject()->getName(),
                $survey->getRecipientName() ?? '',
                $survey->getRecipientEmail(),
                $survey->getSentAt()->format('Y-m-d H:i:s'),
                $survey->getRespondedAt() ? $survey->getRespondedAt()->format('Y-m-d H:i:s') : '',
                $survey->getStatus(),
                $survey->getScore()         ?? '',
                $survey->getCategoryLabel() ?? '',
                $survey->getComment()       ?? '',
                $survey->getExpiresAt()->format('Y-m-d'),
            ];
        }

        // Générer le fichier CSV
        $response = new Response();
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="nps_surveys_'.date('Y-m-d').'.csv"');

        $output = fopen('php://temp', 'r+');
        foreach ($csv as $row) {
            fputcsv($output, $row, ';');
        }
        rewind($output);
        $response->setContent(stream_get_contents($output));
        fclose($output);

        return $response;
    }
}
