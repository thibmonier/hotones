<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Message\SyncBoondManagerTimesMessage;
use App\Repository\BoondManagerSettingsRepository;
use App\Service\BoondManager\BoondManagerClient;
use App\Service\BoondManager\BoondManagerSyncService;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/boond-manager')]
#[IsGranted('ROLE_ADMIN')]
class BoondManagerSettingsController extends AbstractController
{
    #[Route('', name: 'admin_boond_manager_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        BoondManagerSettingsRepository $settingsRepository,
        EntityManagerInterface $em,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'save_settings') {
                // URL API
                $apiBaseUrl = $request->request->get('api_base_url');
                if ($apiBaseUrl !== null) {
                    $settings->apiBaseUrl = trim($apiBaseUrl) !== '' ? trim($apiBaseUrl) : null;
                }

                // Type d'authentification
                $authType = $request->request->get('auth_type');
                if ($authType !== null && in_array($authType, ['basic', 'jwt'], true)) {
                    $settings->authType = $authType;
                }

                // Identifiants Basic Auth
                $apiUsername = $request->request->get('api_username');
                if ($apiUsername !== null) {
                    $settings->apiUsername = trim($apiUsername) !== '' ? trim($apiUsername) : null;
                }

                $apiPassword = $request->request->get('api_password');
                if ($apiPassword !== null && trim($apiPassword) !== '') {
                    $settings->apiPassword = trim($apiPassword);
                }

                // Tokens JWT
                $userToken = $request->request->get('user_token');
                if ($userToken !== null) {
                    $settings->userToken = trim($userToken) !== '' ? trim($userToken) : null;
                }

                $clientToken = $request->request->get('client_token');
                if ($clientToken !== null) {
                    $settings->clientToken = trim($clientToken) !== '' ? trim($clientToken) : null;
                }

                $clientKey = $request->request->get('client_key');
                if ($clientKey !== null) {
                    $settings->clientKey = trim($clientKey) !== '' ? trim($clientKey) : null;
                }

                // Activation
                $settings->enabled = $request->request->getBoolean('enabled');
                $settings->autoSyncEnabled = $request->request->getBoolean('auto_sync_enabled');

                // Frequence de sync
                $syncFrequencyHours = $request->request->getInt('sync_frequency_hours');
                if ($syncFrequencyHours >= 1) {
                    $settings->syncFrequencyHours = $syncFrequencyHours;
                }

                $em->flush();

                $this->addFlash('success', 'Parametres BoondManager enregistres avec succes');

                return $this->redirectToRoute('admin_boond_manager_settings');
            }
        }

        return $this->render('admin/boond_manager/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/test-connection', name: 'admin_boond_manager_test_connection', methods: ['POST'])]
    public function testConnection(
        BoondManagerSettingsRepository $settingsRepository,
        BoondManagerClient $client,
    ): JsonResponse {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'BoondManager n\'est pas configure correctement',
            ]);
        }

        $isConnected = $client->testConnection($settings);

        return new JsonResponse([
            'success' => $isConnected,
            'message' => $isConnected
                ? 'Connexion a l\'API BoondManager reussie !'
                : 'Echec de connexion. Verifiez vos identifiants.',
        ]);
    }

    #[Route('/sync', name: 'admin_boond_manager_sync', methods: ['POST'])]
    public function sync(
        Request $request,
        BoondManagerSettingsRepository $settingsRepository,
        BoondManagerSyncService $syncService,
        MessageBusInterface $messageBus,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'BoondManager n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_boond_manager_settings');
        }

        $startDateStr = $request->request->get('start_date', (new DateTime('-30 days'))->format('Y-m-d'));
        $endDateStr = $request->request->get('end_date', (new DateTime())->format('Y-m-d'));
        $async = $request->request->getBoolean('async');

        try {
            $startDate = new DateTime($startDateStr);
            $endDate = new DateTime($endDateStr);
        } catch (\Exception) {
            $this->addFlash('error', 'Format de date invalide');

            return $this->redirectToRoute('admin_boond_manager_settings');
        }

        if ($async) {
            $messageBus->dispatch(new SyncBoondManagerTimesMessage(
                $settings->getCompany()->getId(),
                $startDate->format('Y-m-d'),
                $endDate->format('Y-m-d'),
            ));

            $this->addFlash('success', 'Synchronisation lancee en arriere-plan');

            return $this->redirectToRoute('admin_boond_manager_settings');
        }

        // Sync synchrone
        $syncResources = $request->request->getBoolean('sync_resources');
        $syncProjects = $request->request->getBoolean('sync_projects');

        $messages = [];

        if ($syncResources) {
            $resourceResult = $syncService->syncResources($settings);
            $messages[] = 'Ressources: ' . $resourceResult->getSummary();
        }

        if ($syncProjects) {
            $projectResult = $syncService->syncProjects($settings);
            $messages[] = 'Projets: ' . $projectResult->getSummary();
        }

        $result = $syncService->sync($settings, $startDate, $endDate);
        $messages[] = 'Temps: ' . $result->getSummary();

        if ($result->success) {
            $this->addFlash('success', implode(' | ', $messages));
        } else {
            $this->addFlash('error', 'Echec: ' . $result->error);
        }

        return $this->redirectToRoute('admin_boond_manager_settings');
    }

    #[Route('/sync-resources', name: 'admin_boond_manager_sync_resources', methods: ['POST'])]
    public function syncResources(
        BoondManagerSettingsRepository $settingsRepository,
        BoondManagerSyncService $syncService,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'BoondManager n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_boond_manager_settings');
        }

        $result = $syncService->syncResources($settings);

        if ($result->success) {
            $this->addFlash('success', 'Ressources: ' . $result->getSummary());
        } else {
            $this->addFlash('error', 'Echec: ' . $result->error);
        }

        return $this->redirectToRoute('admin_boond_manager_settings');
    }

    #[Route('/sync-projects', name: 'admin_boond_manager_sync_projects', methods: ['POST'])]
    public function syncProjects(
        BoondManagerSettingsRepository $settingsRepository,
        BoondManagerSyncService $syncService,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'BoondManager n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_boond_manager_settings');
        }

        $result = $syncService->syncProjects($settings);

        if ($result->success) {
            $this->addFlash('success', 'Projets: ' . $result->getSummary());
        } else {
            $this->addFlash('error', 'Echec: ' . $result->error);
        }

        return $this->redirectToRoute('admin_boond_manager_settings');
    }
}
