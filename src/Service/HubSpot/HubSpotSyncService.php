<?php

declare(strict_types=1);

namespace App\Service\HubSpot;

use App\Entity\Client;
use App\Entity\ClientContact;
use App\Entity\HubSpotSettings;
use App\Repository\ClientContactRepository;
use App\Repository\ClientRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Service de synchronisation des donnees HubSpot vers HotOnes.
 * Synchronise les companies (clients), contacts et deals (affaires).
 */
class HubSpotSyncService
{
    public function __construct(
        private readonly HubSpotClient $hubSpotClient,
        private readonly EntityManagerInterface $entityManager,
        private readonly ClientRepository $clientRepository,
        private readonly ClientContactRepository $contactRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Execute une synchronisation complete selon les parametres configures.
     */
    public function sync(HubSpotSettings $settings): SyncResult
    {
        $result = new SyncResult();

        if (!$settings->isConfigured()) {
            $result->error = 'HubSpot n\'est pas configure correctement';
            $this->updateSyncStatus($settings, $result);

            return $result;
        }

        try {
            $totalCreated = 0;
            $totalUpdated = 0;
            $totalSkipped = 0;

            // Synchroniser les companies (clients)
            if ($settings->syncCompanies) {
                $companiesResult = $this->syncCompanies($settings);
                $totalCreated += $companiesResult->created;
                $totalUpdated += $companiesResult->updated;
                $totalSkipped += $companiesResult->skipped;
                $result->errors = array_merge($result->errors, $companiesResult->errors);
            }

            // Synchroniser les contacts
            if ($settings->syncContacts) {
                $contactsResult = $this->syncContacts($settings);
                $totalCreated += $contactsResult->created;
                $totalUpdated += $contactsResult->updated;
                $totalSkipped += $contactsResult->skipped;
                $result->errors = array_merge($result->errors, $contactsResult->errors);
            }

            $result->success = true;
            $result->created = $totalCreated;
            $result->updated = $totalUpdated;
            $result->skipped = $totalSkipped;
        } catch (Exception $e) {
            $this->logger->error('HubSpot sync failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $result->error = $e->getMessage();
        }

        $this->updateSyncStatus($settings, $result);

        return $result;
    }

    /**
     * Synchronise les companies HubSpot vers les clients HotOnes.
     */
    public function syncCompanies(HubSpotSettings $settings): SyncResult
    {
        $result = new SyncResult();

        try {
            $hubSpotCompanies = $this->hubSpotClient->getCompanies($settings);

            foreach ($hubSpotCompanies as $hsCompany) {
                try {
                    $this->processCompany($settings, $hsCompany, $result);
                } catch (Exception $e) {
                    $result->errors[] = sprintf(
                        'Erreur company %s: %s',
                        $hsCompany['id'] ?? 'unknown',
                        $e->getMessage(),
                    );
                    ++$result->skipped;
                }
            }

            $this->entityManager->flush();
            $result->success = true;
        } catch (Exception $e) {
            $this->logger->error('HubSpot companies sync failed', [
                'error' => $e->getMessage(),
            ]);
            $result->error = $e->getMessage();
        }

        return $result;
    }

    /**
     * Synchronise les contacts HubSpot vers les contacts clients HotOnes.
     */
    public function syncContacts(HubSpotSettings $settings): SyncResult
    {
        $result = new SyncResult();

        try {
            $hubSpotContacts = $this->hubSpotClient->getContacts($settings);

            foreach ($hubSpotContacts as $hsContact) {
                try {
                    $this->processContact($settings, $hsContact, $result);
                } catch (Exception $e) {
                    $result->errors[] = sprintf(
                        'Erreur contact %s: %s',
                        $hsContact['id'] ?? 'unknown',
                        $e->getMessage(),
                    );
                    ++$result->skipped;
                }
            }

            $this->entityManager->flush();
            $result->success = true;
        } catch (Exception $e) {
            $this->logger->error('HubSpot contacts sync failed', [
                'error' => $e->getMessage(),
            ]);
            $result->error = $e->getMessage();
        }

        return $result;
    }

    /**
     * Recupere les affaires (deals) en cours non signees depuis HubSpot.
     * Cette methode retourne les donnees brutes de HubSpot pour affichage.
     *
     * @return array<string, mixed>
     */
    public function getOpenDeals(HubSpotSettings $settings): array
    {
        if (!$settings->isConfigured()) {
            return [
                'success' => false,
                'error'   => 'HubSpot n\'est pas configure',
                'deals'   => [],
            ];
        }

        try {
            $excludedStages = $settings->getExcludedStagesList();
            $pipelineIds    = $settings->getPipelineIds();

            $deals = $this->hubSpotClient->getDeals($settings, $excludedStages, $pipelineIds);

            // Enrichir les deals avec les infos de pipeline
            $pipelines   = $this->hubSpotClient->getDealPipelines($settings);
            $pipelineMap = [];
            $stageMap    = [];

            foreach ($pipelines as $pipeline) {
                $pipelineMap[$pipeline['id']] = $pipeline['label'] ?? $pipeline['id'];
                foreach ($pipeline['stages'] ?? [] as $stage) {
                    $stageMap[$stage['id']] = [
                        'label'        => $stage['label']        ?? $stage['id'],
                        'displayOrder' => $stage['displayOrder'] ?? 0,
                    ];
                }
            }

            // Formater les deals pour l'affichage
            $formattedDeals = [];
            foreach ($deals as $deal) {
                $props      = $deal['properties'] ?? [];
                $pipelineId = $props['pipeline']  ?? '';
                $stageId    = $props['dealstage'] ?? '';

                $formattedDeals[] = [
                    'id'           => $deal['id'],
                    'name'         => $props['dealname']        ?? 'Sans nom',
                    'amount'       => $props['amount']          ?? null,
                    'pipeline'     => $pipelineMap[$pipelineId] ?? $pipelineId,
                    'pipelineId'   => $pipelineId,
                    'stage'        => $stageMap[$stageId]['label'] ?? $stageId,
                    'stageId'      => $stageId,
                    'closeDate'    => $props['closedate']           ?? null,
                    'createDate'   => $props['createdate']          ?? null,
                    'lastModified' => $props['hs_lastmodifieddate'] ?? null,
                    'associations' => $deal['associations']         ?? [],
                ];
            }

            // Trier par date de creation (plus recents en premier)
            usort($formattedDeals, function ($a, $b) {
                return ($b['createDate'] ?? '') <=> ($a['createDate'] ?? '');
            });

            return [
                'success'   => true,
                'deals'     => $formattedDeals,
                'count'     => count($formattedDeals),
                'pipelines' => $pipelines,
            ];
        } catch (Exception $e) {
            $this->logger->error('HubSpot: Failed to get open deals', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'deals'   => [],
            ];
        }
    }

    /**
     * Recupere la liste des clients HubSpot avec leurs contacts associes.
     *
     * @return array<string, mixed>
     */
    public function getClientsWithContacts(HubSpotSettings $settings): array
    {
        if (!$settings->isConfigured()) {
            return [
                'success' => false,
                'error'   => 'HubSpot n\'est pas configure',
                'clients' => [],
            ];
        }

        try {
            $companies = $this->hubSpotClient->getCompanies($settings);

            $formattedClients = [];
            foreach ($companies as $company) {
                $props     = $company['properties'] ?? [];
                $companyId = $company['id'];

                // Recuperer les contacts associes
                $associatedContacts = [];
                $associations       = $company['associations']['contacts']['results'] ?? [];

                foreach ($associations as $contactAssoc) {
                    $contactId = $contactAssoc['id'];
                    $contact   = $this->hubSpotClient->getContact($settings, $contactId);
                    if ($contact !== null) {
                        $contactProps         = $contact['properties'] ?? [];
                        $associatedContacts[] = [
                            'id'          => $contact['id'],
                            'firstName'   => $contactProps['firstname']   ?? '',
                            'lastName'    => $contactProps['lastname']    ?? '',
                            'email'       => $contactProps['email']       ?? '',
                            'phone'       => $contactProps['phone']       ?? '',
                            'mobilePhone' => $contactProps['mobilephone'] ?? '',
                            'jobTitle'    => $contactProps['jobtitle']    ?? '',
                        ];
                    }
                }

                $formattedClients[] = [
                    'id'           => $companyId,
                    'name'         => $props['name']     ?? 'Sans nom',
                    'domain'       => $props['domain']   ?? '',
                    'website'      => $props['website']  ?? '',
                    'phone'        => $props['phone']    ?? '',
                    'industry'     => $props['industry'] ?? '',
                    'city'         => $props['city']     ?? '',
                    'country'      => $props['country']  ?? '',
                    'contacts'     => $associatedContacts,
                    'contactCount' => count($associatedContacts),
                ];
            }

            // Trier par nom
            usort($formattedClients, function ($a, $b) {
                return strcasecmp($a['name'], $b['name']);
            });

            return [
                'success' => true,
                'clients' => $formattedClients,
                'count'   => count($formattedClients),
            ];
        } catch (Exception $e) {
            $this->logger->error('HubSpot: Failed to get clients with contacts', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error'   => $e->getMessage(),
                'clients' => [],
            ];
        }
    }

    /**
     * Traite une company HubSpot et la synchronise vers un client HotOnes.
     */
    private function processCompany(HubSpotSettings $settings, array $hsCompany, SyncResult $result): void
    {
        $hubSpotId = $hsCompany['id'] ?? null;
        if ($hubSpotId === null) {
            ++$result->skipped;

            return;
        }

        $props = $hsCompany['properties'] ?? [];
        $name  = $props['name']           ?? '';

        if ($name === '') {
            ++$result->skipped;

            return;
        }

        // Chercher un client existant par nom (simplification, idealement utiliser un champ hubspot_id)
        $existingClient = $this->clientRepository->findOneBy([
            'name'    => $name,
            'company' => $settings->getCompany(),
        ]);

        if ($existingClient !== null) {
            // Mettre a jour
            $existingClient->website     = $props['website']     ?? $existingClient->website;
            $existingClient->description = $props['description'] ?? $existingClient->description;
            ++$result->updated;
        } else {
            // Creer un nouveau client
            $client              = new Client();
            $client->company     = $settings->getCompany();
            $client->name        = $name;
            $client->website     = $props['website']     ?? null;
            $client->description = $props['description'] ?? null;
            $this->entityManager->persist($client);
            ++$result->created;
        }
    }

    /**
     * Traite un contact HubSpot et le synchronise vers un contact client HotOnes.
     */
    private function processContact(HubSpotSettings $settings, array $hsContact, SyncResult $result): void
    {
        $hubSpotId = $hsContact['id'] ?? null;
        if ($hubSpotId === null) {
            ++$result->skipped;

            return;
        }

        $props     = $hsContact['properties'] ?? [];
        $email     = $props['email']          ?? '';
        $firstName = $props['firstname']      ?? '';
        $lastName  = $props['lastname']       ?? '';

        if ($email === '' && $firstName === '' && $lastName === '') {
            ++$result->skipped;

            return;
        }

        // Trouver le client associe via les associations
        $associations = $hsContact['associations']['companies']['results'] ?? [];
        $client       = null;

        if (!empty($associations)) {
            // Recuperer la premiere company associee
            $companyAssoc = $associations[0];
            $hsCompany    = $this->hubSpotClient->getCompany($settings, $companyAssoc['id']);
            if ($hsCompany !== null) {
                $companyName = $hsCompany['properties']['name'] ?? '';
                if ($companyName !== '') {
                    $client = $this->clientRepository->findOneBy([
                        'name'    => $companyName,
                        'company' => $settings->getCompany(),
                    ]);
                }
            }
        }

        if ($client === null) {
            // Pas de client associe, ignorer le contact
            ++$result->skipped;

            return;
        }

        // Chercher un contact existant par email
        $existingContact = null;
        if ($email !== '') {
            $existingContact = $this->contactRepository->findOneBy([
                'email'   => $email,
                'company' => $settings->getCompany(),
            ]);
        }

        if ($existingContact !== null) {
            // Mettre a jour
            $existingContact->firstName     = $firstName !== '' ? $firstName : $existingContact->firstName;
            $existingContact->lastName      = $lastName  !== '' ? $lastName : $existingContact->lastName;
            $existingContact->phone         = $props['phone']       ?? $existingContact->phone;
            $existingContact->mobilePhone   = $props['mobilephone'] ?? $existingContact->mobilePhone;
            $existingContact->positionTitle = $props['jobtitle']    ?? $existingContact->positionTitle;
            ++$result->updated;
        } else {
            // Creer un nouveau contact
            $contact          = new ClientContact();
            $contact->company = $settings->getCompany();
            $contact->setClient($client);
            $contact->firstName     = $firstName;
            $contact->lastName      = $lastName;
            $contact->email         = $email !== '' ? $email : null;
            $contact->phone         = $props['phone']       ?? null;
            $contact->mobilePhone   = $props['mobilephone'] ?? null;
            $contact->positionTitle = $props['jobtitle']    ?? null;
            $this->entityManager->persist($contact);
            ++$result->created;
        }
    }

    /**
     * Met a jour le statut de synchronisation dans les settings.
     */
    private function updateSyncStatus(HubSpotSettings $settings, SyncResult $result): void
    {
        $settings->lastSyncAt     = new DateTime();
        $settings->lastSyncStatus = $result->success ? 'success' : 'error';
        $settings->lastSyncError  = $result->error;
        $settings->lastSyncCount  = $result->getTotal();

        $this->entityManager->flush();
    }
}
