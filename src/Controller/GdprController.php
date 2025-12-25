<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\AccountDeletionRequest;
use App\Entity\CookieConsent;
use App\Repository\AccountDeletionRequestRepository;
use App\Service\GdprDataExportService;
use App\Service\GdprEmailService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Contrôleur pour la conformité RGPD.
 * Gère le consentement cookies, la politique de confidentialité et les droits des utilisateurs.
 */
#[Route('/gdpr')]
class GdprController extends AbstractController
{
    /**
     * Sauvegarde le consentement cookies de l'utilisateur.
     * Appelé par le JavaScript de la bannière de cookies.
     */
    #[Route('/cookie-consent', name: 'cookie_consent_save', methods: ['POST'])]
    public function saveCookieConsent(Request $request, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Créer l'entité CookieConsent pour traçabilité RGPD
        $consent = new CookieConsent();
        $consent->setUser($this->getUser());
        $consent->setEssential($data['essential'] ?? true);
        $consent->setFunctional($data['functional'] ?? false);
        $consent->setAnalytics($data['analytics'] ?? false);
        $consent->setVersion($data['version'] ?? '1.0');
        $consent->setIpAddress($request->getClientIp());
        $consent->setUserAgent($request->headers->get('User-Agent'));

        $em->persist($consent);
        $em->flush();

        // Log pour debugging
        $this->container->get('logger')->info('Cookie consent saved', [
            'consent_id' => $consent->getId(),
            'user_id'    => $this->getUser()?->getId(),
            'ip'         => $consent->getIpAddress(),
        ]);

        return new JsonResponse([
            'success' => true,
            'message' => 'Préférences enregistrées',
        ]);
    }

    /**
     * Politique de confidentialité (Privacy Policy).
     * Page publique détaillant le traitement des données personnelles.
     */
    #[Route('/privacy-policy', name: 'privacy_policy', methods: ['GET'])]
    public function privacyPolicy(): Response
    {
        return $this->render('gdpr/privacy_policy.html.twig', [
            'last_updated' => new DateTime('2025-01-01'), // Date de dernière mise à jour
        ]);
    }

    /**
     * Mentions légales.
     */
    #[Route('/legal-notice', name: 'legal_notice', methods: ['GET'])]
    public function legalNotice(): Response
    {
        return $this->render('gdpr/legal_notice.html.twig');
    }

    /**
     * Guide utilisateur RGPD.
     * Documentation complète des droits et processus RGPD.
     */
    #[Route('/user-guide', name: 'gdpr_user_guide', methods: ['GET'])]
    public function userGuide(): Response
    {
        return $this->render('gdpr/user_guide.html.twig');
    }

    /**
     * Page de gestion des données personnelles (droits RGPD).
     * Accès réservé aux utilisateurs connectés.
     */
    #[Route('/my-data', name: 'gdpr_my_data', methods: ['GET'])]
    public function myData(): Response
    {
        if (!$this->getUser()) {
            $this->addFlash('warning', 'Vous devez être connecté pour accéder à vos données personnelles.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('gdpr/my_data.html.twig', [
            'user' => $this->getUser(),
        ]);
    }

    /**
     * Export des données personnelles (droit à la portabilité).
     * Format JSON conforme RGPD.
     */
    #[Route('/export-my-data', name: 'gdpr_export_data', methods: ['POST'])]
    public function exportMyData(GdprDataExportService $exportService): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        // Export complet de toutes les données utilisateur
        $userData = $exportService->exportUserData($user);

        $filename = sprintf('my_data_%s_%s.json', $user->getEmail(), date('Y-m-d'));

        $response = new Response(json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    /**
     * Demande de suppression du compte (droit à l'oubli).
     * Créé une demande et envoie un email de confirmation.
     */
    #[Route('/request-account-deletion', name: 'gdpr_request_deletion', methods: ['POST'])]
    public function requestAccountDeletion(
        Request $request,
        EntityManagerInterface $em,
        AccountDeletionRequestRepository $deletionRepository,
        GdprEmailService $emailService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        // Vérifier s'il n'y a pas déjà une demande active
        $existingRequest = $deletionRepository->findActiveDeletionRequestForUser($user);
        if ($existingRequest) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Une demande de suppression est déjà en cours pour votre compte.',
            ], 400);
        }

        // Créer la demande de suppression
        $deletionRequest = new AccountDeletionRequest();
        $deletionRequest->setUser($user);
        $deletionRequest->setIpAddress($request->getClientIp());

        $em->persist($deletionRequest);
        $em->flush();

        // Envoyer l'email de confirmation avec le lien contenant le token
        try {
            $emailService->sendDeletionRequestConfirmation($deletionRequest);
            $message = 'Demande enregistrée. Consultez votre email pour confirmer (lien valide 48h).';
        } catch (Exception $e) {
            $this->container->get('logger')->error('Failed to send deletion request email', [
                'user_id'    => $user->getId(),
                'request_id' => $deletionRequest->getId(),
                'error'      => $e->getMessage(),
            ]);
            $message = 'Demande enregistrée mais l\'email de confirmation n\'a pas pu être envoyé. Contactez le support.';
        }

        $this->addFlash('info', 'Demande de suppression enregistrée. Vérifiez votre email pour confirmer (lien valide 48h).');

        return new JsonResponse([
            'success' => true,
            'message' => $message,
        ]);
    }

    /**
     * Confirmation de la demande de suppression (via lien email).
     * Active la période de grâce de 30 jours.
     */
    #[Route('/confirm-account-deletion/{token}', name: 'gdpr_confirm_deletion', methods: ['GET'])]
    public function confirmAccountDeletion(
        string $token,
        EntityManagerInterface $em,
        AccountDeletionRequestRepository $deletionRepository,
        GdprEmailService $emailService
    ): Response {
        $deletionRequest = $deletionRepository->findByConfirmationToken($token);

        if (!$deletionRequest) {
            $this->addFlash('error', 'Lien de confirmation invalide ou expiré.');

            return $this->redirectToRoute('home');
        }

        if ($deletionRequest->isConfirmationTokenExpired()) {
            $this->addFlash('error', 'Le lien de confirmation a expiré (48h). Veuillez refaire une demande.');

            return $this->redirectToRoute('gdpr_my_data');
        }

        // Confirmer la demande et planifier la suppression dans 30 jours
        $deletionRequest->confirm();
        $em->flush();

        // Envoyer l'email de confirmation (période de grâce commence)
        try {
            $emailService->sendDeletionConfirmed($deletionRequest);
        } catch (Exception $e) {
            $this->container->get('logger')->error('Failed to send deletion confirmed email', [
                'user_id'    => $deletionRequest->getUser()->getId(),
                'request_id' => $deletionRequest->getId(),
                'error'      => $e->getMessage(),
            ]);
        }

        $this->addFlash('warning', sprintf(
            'Votre compte sera supprimé le %s. Vous pouvez annuler cette demande avant cette date.',
            $deletionRequest->getScheduledDeletionAt()->format('d/m/Y à H:i'),
        ));

        return $this->redirectToRoute('gdpr_my_data');
    }

    /**
     * Annulation d'une demande de suppression (pendant la période de grâce).
     */
    #[Route('/cancel-account-deletion', name: 'gdpr_cancel_deletion', methods: ['POST'])]
    public function cancelAccountDeletion(
        EntityManagerInterface $em,
        AccountDeletionRequestRepository $deletionRepository,
        GdprEmailService $emailService
    ): JsonResponse {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        $deletionRequest = $deletionRepository->findActiveDeletionRequestForUser($user);
        if (!$deletionRequest) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Aucune demande de suppression active trouvée.',
            ], 404);
        }

        if (!$deletionRequest->isInGracePeriod()) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Cette demande ne peut plus être annulée.',
            ], 400);
        }

        // Annuler la demande
        $deletionRequest->cancel();
        $em->flush();

        // Envoyer l'email de confirmation d'annulation
        try {
            $emailService->sendDeletionCancelled($deletionRequest);
        } catch (Exception $e) {
            $this->container->get('logger')->error('Failed to send deletion cancelled email', [
                'user_id'    => $user->getId(),
                'request_id' => $deletionRequest->getId(),
                'error'      => $e->getMessage(),
            ]);
        }

        $this->addFlash('success', 'Votre demande de suppression de compte a été annulée.');

        return new JsonResponse([
            'success' => true,
            'message' => 'Demande de suppression annulée avec succès.',
        ]);
    }
}
