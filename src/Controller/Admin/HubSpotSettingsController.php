<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Repository\HubSpotSettingsRepository;
use App\Service\HubSpot\HubSpotClient;
use App\Service\HubSpot\HubSpotSyncService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/hubspot')]
#[IsGranted('ROLE_ADMIN')]
class HubSpotSettingsController extends AbstractController
{
    #[Route('', name: 'admin_hubspot_settings', methods: ['GET', 'POST'])]
    public function index(
        Request $request,
        HubSpotSettingsRepository $settingsRepository,
        EntityManagerInterface $em,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if ($request->isMethod('POST')) {
            $action = $request->request->get('action');

            if ($action === 'save_settings') {
                // Token d'acces
                $accessToken = $request->request->get('access_token');
                if ($accessToken !== null && trim($accessToken) !== '') {
                    $settings->accessToken = trim($accessToken);
                }

                // Portal ID
                $portalId = $request->request->get('portal_id');
                if ($portalId !== null) {
                    $settings->portalId = trim($portalId) !== '' ? trim($portalId) : null;
                }

                // Activation
                $settings->enabled         = $request->request->getBoolean('enabled');
                $settings->autoSyncEnabled = $request->request->getBoolean('auto_sync_enabled');

                // Options de synchronisation
                $settings->syncDeals     = $request->request->getBoolean('sync_deals');
                $settings->syncCompanies = $request->request->getBoolean('sync_companies');
                $settings->syncContacts  = $request->request->getBoolean('sync_contacts');

                // Filtres
                $pipelineFilter = $request->request->get('pipeline_filter');
                if ($pipelineFilter !== null) {
                    $settings->pipelineFilter = trim($pipelineFilter) !== '' ? trim($pipelineFilter) : null;
                }

                $excludedStages = $request->request->get('excluded_stages');
                if ($excludedStages !== null) {
                    $settings->excludedStages = trim($excludedStages) !== '' ? trim($excludedStages) : null;
                }

                // Frequence de sync
                $syncFrequencyHours = $request->request->getInt('sync_frequency_hours');
                if ($syncFrequencyHours >= 1) {
                    $settings->syncFrequencyHours = $syncFrequencyHours;
                }

                $em->flush();

                $this->addFlash('success', 'Parametres HubSpot enregistres avec succes');

                return $this->redirectToRoute('admin_hubspot_settings');
            }
        }

        return $this->render('admin/hubspot/index.html.twig', [
            'settings' => $settings,
        ]);
    }

    #[Route('/test-connection', name: 'admin_hubspot_test_connection', methods: ['POST'])]
    public function testConnection(
        HubSpotSettingsRepository $settingsRepository,
        HubSpotClient $client,
    ): JsonResponse {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured()) {
            return new JsonResponse([
                'success' => false,
                'message' => 'HubSpot n\'est pas configure correctement',
            ]);
        }

        $isConnected = $client->testConnection($settings);

        $additionalInfo = null;
        if ($isConnected) {
            $accountInfo = $client->getAccountInfo($settings);
            if ($accountInfo !== null) {
                $additionalInfo = sprintf(
                    'Connecte au portal: %s',
                    $accountInfo['portalId'] ?? 'N/A',
                );
            }
        }

        return new JsonResponse([
            'success' => $isConnected,
            'message' => $isConnected
                ? 'Connexion a l\'API HubSpot reussie !'
                : 'Echec de connexion. Verifiez votre token d\'acces.',
            'info' => $additionalInfo,
        ]);
    }

    #[Route('/sync', name: 'admin_hubspot_sync', methods: ['POST'])]
    public function sync(
        HubSpotSettingsRepository $settingsRepository,
        HubSpotSyncService $syncService,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'HubSpot n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_hubspot_settings');
        }

        $result = $syncService->sync($settings);

        if ($result->success) {
            $this->addFlash('success', 'Synchronisation: '.$result->getSummary());
        } else {
            $this->addFlash('error', 'Echec: '.$result->error);
        }

        return $this->redirectToRoute('admin_hubspot_settings');
    }

    #[Route('/sync-companies', name: 'admin_hubspot_sync_companies', methods: ['POST'])]
    public function syncCompanies(
        HubSpotSettingsRepository $settingsRepository,
        HubSpotSyncService $syncService,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'HubSpot n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_hubspot_settings');
        }

        $result = $syncService->syncCompanies($settings);

        if ($result->success) {
            $this->addFlash('success', 'Clients: '.$result->getSummary());
        } else {
            $this->addFlash('error', 'Echec: '.$result->error);
        }

        return $this->redirectToRoute('admin_hubspot_settings');
    }

    #[Route('/sync-contacts', name: 'admin_hubspot_sync_contacts', methods: ['POST'])]
    public function syncContacts(
        HubSpotSettingsRepository $settingsRepository,
        HubSpotSyncService $syncService,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'HubSpot n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_hubspot_settings');
        }

        $result = $syncService->syncContacts($settings);

        if ($result->success) {
            $this->addFlash('success', 'Contacts: '.$result->getSummary());
        } else {
            $this->addFlash('error', 'Echec: '.$result->error);
        }

        return $this->redirectToRoute('admin_hubspot_settings');
    }

    #[Route('/deals', name: 'admin_hubspot_deals', methods: ['GET'])]
    public function deals(
        HubSpotSettingsRepository $settingsRepository,
        HubSpotSyncService $syncService,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'HubSpot n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_hubspot_settings');
        }

        $dealsData = $syncService->getOpenDeals($settings);

        return $this->render('admin/hubspot/deals.html.twig', [
            'settings'  => $settings,
            'dealsData' => $dealsData,
        ]);
    }

    #[Route('/clients', name: 'admin_hubspot_clients', methods: ['GET'])]
    public function clients(
        HubSpotSettingsRepository $settingsRepository,
        HubSpotSyncService $syncService,
    ): Response {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $this->addFlash('error', 'HubSpot n\'est pas configure ou est desactive');

            return $this->redirectToRoute('admin_hubspot_settings');
        }

        $clientsData = $syncService->getClientsWithContacts($settings);

        return $this->render('admin/hubspot/clients.html.twig', [
            'settings'    => $settings,
            'clientsData' => $clientsData,
        ]);
    }

    #[Route('/pipelines', name: 'admin_hubspot_pipelines', methods: ['GET'])]
    public function pipelines(
        HubSpotSettingsRepository $settingsRepository,
        HubSpotClient $client,
    ): JsonResponse {
        $settings = $settingsRepository->getSettings();

        if (!$settings->isConfigured()) {
            return new JsonResponse([
                'success'   => false,
                'message'   => 'HubSpot n\'est pas configure',
                'pipelines' => [],
            ]);
        }

        $pipelines = $client->getDealPipelines($settings);

        return new JsonResponse([
            'success'   => true,
            'pipelines' => $pipelines,
        ]);
    }
}
