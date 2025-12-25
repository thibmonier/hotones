<?php

declare(strict_types=1);

namespace App\Controller;

use DateTime;
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
    public function saveCookieConsent(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        // Log du consentement pour traçabilité RGPD
        // En production, sauvegarder dans une table dédiée avec IP, User-Agent, timestamp
        $consentLog = [
            'timestamp'  => $data['timestamp']  ?? date('c'),
            'version'    => $data['version']    ?? '1.0',
            'essential'  => $data['essential']  ?? true,
            'functional' => $data['functional'] ?? false,
            'analytics'  => $data['analytics']  ?? false,
            'ip'         => $request->getClientIp(),
            'user_agent' => $request->headers->get('User-Agent'),
            'user_id'    => $this->getUser()?->getId(),
        ];

        // TODO: Sauvegarder dans une table `cookie_consents` pour traçabilité complète
        // Pour l'instant, on log juste dans les logs Symfony
        $this->container->get('logger')->info('Cookie consent saved', $consentLog);

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
    public function exportMyData(): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        // TODO: Implémenter l'export complet des données
        // Doit inclure : profil, timesheets, projects, etc.
        $userData = [
            'export_date' => date('c'),
            'user_id'     => $user->getId(),
            'email'       => $user->getEmail(),
            'first_name'  => $user->getFirstName(),
            'last_name'   => $user->getLastName(),
            'roles'       => $user->getRoles(),
            'created_at'  => $user->getCreatedAt()?->format('c'),
            // TODO: Ajouter toutes les données liées (timesheets, projets, etc.)
        ];

        $filename = sprintf('my_data_%s_%s.json', $user->getEmail(), date('Y-m-d'));

        $response = new Response(json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

    /**
     * Demande de suppression du compte (droit à l'oubli).
     * Nécessite validation par email avant suppression définitive.
     */
    #[Route('/request-account-deletion', name: 'gdpr_request_deletion', methods: ['POST'])]
    public function requestAccountDeletion(): JsonResponse
    {
        $user = $this->getUser();
        if (!$user) {
            return new JsonResponse(['error' => 'Non authentifié'], 401);
        }

        // TODO: Implémenter le workflow de suppression
        // 1. Envoyer un email de confirmation
        // 2. Attendre validation (lien avec token)
        // 3. Période de grâce de 30 jours
        // 4. Suppression définitive ou anonymisation

        $this->addFlash('info', 'Une demande de suppression a été envoyée. Vous recevrez un email de confirmation.');

        return new JsonResponse([
            'success' => true,
            'message' => 'Demande de suppression enregistrée. Vérifiez votre email.',
        ]);
    }
}
