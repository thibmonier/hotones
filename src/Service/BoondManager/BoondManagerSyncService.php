<?php

declare(strict_types=1);

namespace App\Service\BoondManager;

use App\Entity\BoondManagerSettings;
use App\Entity\Company;
use App\Entity\Contributor;
use App\Entity\Project;
use App\Entity\Timesheet;
use App\Repository\ContributorRepository;
use App\Repository\ProjectRepository;
use App\Repository\TimesheetRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Psr\Log\LoggerInterface;

/**
 * Service de synchronisation des temps passes depuis BoondManager.
 */
class BoondManagerSyncService
{
    public function __construct(
        private readonly BoondManagerClient $client,
        private readonly EntityManagerInterface $em,
        private readonly ContributorRepository $contributorRepository,
        private readonly ProjectRepository $projectRepository,
        private readonly TimesheetRepository $timesheetRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Synchronise les temps passes pour une entreprise.
     *
     * @return SyncResult Resultat de la synchronisation
     */
    public function sync(
        BoondManagerSettings $settings,
        DateTimeInterface $startDate,
        DateTimeInterface $endDate,
    ): SyncResult {
        $result = new SyncResult();

        if (!$settings->isConfigured()) {
            $result->success = false;
            $result->error   = 'BoondManager n\'est pas configure correctement';

            return $result;
        }

        if (!$settings->enabled) {
            $result->success = false;
            $result->error   = 'La synchronisation BoondManager est desactivee';

            return $result;
        }

        try {
            $this->logger->info('Starting BoondManager sync', [
                'startDate' => $startDate->format('Y-m-d'),
                'endDate'   => $endDate->format('Y-m-d'),
            ]);

            // Recuperer les temps depuis BoondManager
            $boondTimes = $this->client->getTimes($settings, $startDate, $endDate);

            $company = $settings->getCompany();

            foreach ($boondTimes as $boondTime) {
                try {
                    $this->processTimeEntry($boondTime, $company, $settings, $result);
                } catch (Exception $e) {
                    $result->errors[] = sprintf(
                        'Erreur traitement temps #%s: %s',
                        $boondTime['id'] ?? 'unknown',
                        $e->getMessage(),
                    );
                    ++$result->skipped;
                    $this->logger->warning('Failed to process time entry', [
                        'timeId' => $boondTime['id'] ?? null,
                        'error'  => $e->getMessage(),
                    ]);
                }
            }

            $this->em->flush();

            // Mise a jour du statut de synchronisation
            $settings->lastSyncAt     = new DateTime();
            $settings->lastSyncStatus = 'success';
            $settings->lastSyncError  = null;
            $settings->lastSyncCount  = $result->created + $result->updated;
            $this->em->flush();

            $result->success = true;

            $this->logger->info('BoondManager sync completed', [
                'created' => $result->created,
                'updated' => $result->updated,
                'skipped' => $result->skipped,
            ]);
        } catch (Exception $e) {
            $result->success = false;
            $result->error   = $e->getMessage();

            // Mise a jour du statut d'erreur
            $settings->lastSyncAt     = new DateTime();
            $settings->lastSyncStatus = 'error';
            $settings->lastSyncError  = $e->getMessage();
            $this->em->flush();

            $this->logger->error('BoondManager sync failed', [
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * Synchronise les ressources (contributeurs) depuis BoondManager.
     */
    public function syncResources(BoondManagerSettings $settings): SyncResult
    {
        $result = new SyncResult();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $result->success = false;
            $result->error   = 'BoondManager n\'est pas configure ou est desactive';

            return $result;
        }

        try {
            $boondResources = $this->client->getResources($settings);
            $company        = $settings->getCompany();

            foreach ($boondResources as $resource) {
                try {
                    $this->processResource($resource, $company, $result);
                } catch (Exception $e) {
                    $result->errors[] = sprintf(
                        'Erreur traitement ressource #%s: %s',
                        $resource['id'] ?? 'unknown',
                        $e->getMessage(),
                    );
                    ++$result->skipped;
                }
            }

            $this->em->flush();
            $result->success = true;
        } catch (Exception $e) {
            $result->success = false;
            $result->error   = $e->getMessage();
        }

        return $result;
    }

    /**
     * Synchronise les projets depuis BoondManager.
     */
    public function syncProjects(BoondManagerSettings $settings): SyncResult
    {
        $result = new SyncResult();

        if (!$settings->isConfigured() || !$settings->enabled) {
            $result->success = false;
            $result->error   = 'BoondManager n\'est pas configure ou est desactive';

            return $result;
        }

        try {
            $boondProjects = $this->client->getProjects($settings);
            $company       = $settings->getCompany();

            foreach ($boondProjects as $project) {
                try {
                    $this->processProject($project, $company, $result);
                } catch (Exception $e) {
                    $result->errors[] = sprintf(
                        'Erreur traitement projet #%s: %s',
                        $project['id'] ?? 'unknown',
                        $e->getMessage(),
                    );
                    ++$result->skipped;
                }
            }

            $this->em->flush();
            $result->success = true;
        } catch (Exception $e) {
            $result->success = false;
            $result->error   = $e->getMessage();
        }

        return $result;
    }

    /**
     * Traite une entree de temps BoondManager.
     *
     * @param array<string, mixed> $boondTime
     */
    private function processTimeEntry(
        array $boondTime,
        Company $company,
        BoondManagerSettings $settings,
        SyncResult $result,
    ): void {
        // Extraire les donnees de l'entree de temps
        $boondTimeId = (int) ($boondTime['id'] ?? 0);
        $resourceId  = $this->extractResourceId($boondTime);
        $projectId   = $this->extractProjectId($boondTime);
        $date        = $this->extractDate($boondTime);
        $hours       = $this->extractHours($boondTime);

        if ($boondTimeId === 0 || $resourceId === null || $date === null || $hours === null) {
            ++$result->skipped;

            return;
        }

        // Trouver le contributeur correspondant
        $contributor = $this->findContributorByBoondId($resourceId, $company);

        if (!$contributor) {
            // Essayer de creer le contributeur depuis BoondManager
            $boondResource = $this->client->getResource($settings, $resourceId);
            if ($boondResource) {
                $contributor = $this->createContributorFromBoond($boondResource, $company);
            } else {
                $result->errors[] = sprintf('Contributeur non trouve pour resource_id=%d', $resourceId);
                ++$result->skipped;

                return;
            }
        }

        // Trouver le projet correspondant
        $project = null;
        if ($projectId !== null) {
            $project = $this->findProjectByBoondId($projectId, $company);

            if (!$project) {
                // Essayer de creer le projet depuis BoondManager
                $boondProject = $this->client->getProject($settings, $projectId);
                if ($boondProject) {
                    $project = $this->createProjectFromBoond($boondProject, $company);
                }
            }
        }

        if (!$project) {
            $result->errors[] = sprintf('Projet non trouve pour project_id=%d', $projectId ?? 0);
            ++$result->skipped;

            return;
        }

        // Chercher une entree existante pour eviter les doublons
        $existingTimesheet = $this->findExistingTimesheet($contributor, $project, $date, $company);

        if ($existingTimesheet) {
            // Mettre a jour l'entree existante
            $existingTimesheet->hours = $hours;
            $existingTimesheet->notes = $boondTime['attributes']['comment'] ?? $existingTimesheet->notes;
            ++$result->updated;
        } else {
            // Creer une nouvelle entree
            $timesheet              = new Timesheet();
            $timesheet->company     = $company;
            $timesheet->contributor = $contributor;
            $timesheet->project     = $project;
            $timesheet->date        = $date;
            $timesheet->hours       = $hours;
            $timesheet->notes       = sprintf('[Boond #%d] %s', $boondTimeId, $boondTime['attributes']['comment'] ?? '');

            $this->em->persist($timesheet);
            ++$result->created;
        }
    }

    /**
     * Traite une ressource BoondManager.
     *
     * @param array<string, mixed> $resource
     */
    private function processResource(array $resource, Company $company, SyncResult $result): void
    {
        $boondId = (int) ($resource['id'] ?? 0);
        if ($boondId === 0) {
            ++$result->skipped;

            return;
        }

        $contributor = $this->findContributorByBoondId($boondId, $company);

        if ($contributor) {
            // Mettre a jour le contributeur existant
            $this->updateContributorFromBoond($contributor, $resource);
            ++$result->updated;
        } else {
            // Creer un nouveau contributeur
            $this->createContributorFromBoond($resource, $company);
            ++$result->created;
        }
    }

    /**
     * Traite un projet BoondManager.
     *
     * @param array<string, mixed> $project
     */
    private function processProject(array $project, Company $company, SyncResult $result): void
    {
        $boondId = (int) ($project['id'] ?? 0);
        if ($boondId === 0) {
            ++$result->skipped;

            return;
        }

        $existingProject = $this->findProjectByBoondId($boondId, $company);

        if ($existingProject) {
            $this->updateProjectFromBoond($existingProject, $project);
            ++$result->updated;
        } else {
            $this->createProjectFromBoond($project, $company);
            ++$result->created;
        }
    }

    private function findContributorByBoondId(int $boondId, Company $company): ?Contributor
    {
        return $this->contributorRepository->findOneBy([
            'boondManagerId' => $boondId,
            'company'        => $company,
        ]);
    }

    private function findProjectByBoondId(int $boondId, Company $company): ?Project
    {
        return $this->projectRepository->findOneBy([
            'boondManagerId' => $boondId,
            'company'        => $company,
        ]);
    }

    private function findExistingTimesheet(
        Contributor $contributor,
        Project $project,
        DateTimeInterface $date,
        Company $company,
    ): ?Timesheet {
        return $this->timesheetRepository->findOneBy([
            'contributor' => $contributor,
            'project'     => $project,
            'date'        => $date,
            'company'     => $company,
        ]);
    }

    /**
     * @param array<string, mixed> $boondResource
     */
    private function createContributorFromBoond(array $boondResource, Company $company): Contributor
    {
        $contributor                 = new Contributor();
        $contributor->company        = $company;
        $contributor->boondManagerId = (int) $boondResource['id'];
        $contributor->firstName      = $boondResource['attributes']['firstName'] ?? 'Inconnu';
        $contributor->lastName       = $boondResource['attributes']['lastName']  ?? 'Inconnu';
        $contributor->email          = $boondResource['attributes']['email1']    ?? null;
        $contributor->active         = true;

        $this->em->persist($contributor);

        return $contributor;
    }

    /**
     * @param array<string, mixed> $boondResource
     */
    private function updateContributorFromBoond(Contributor $contributor, array $boondResource): void
    {
        if (isset($boondResource['attributes']['firstName'])) {
            $contributor->firstName = $boondResource['attributes']['firstName'];
        }
        if (isset($boondResource['attributes']['lastName'])) {
            $contributor->lastName = $boondResource['attributes']['lastName'];
        }
        if (isset($boondResource['attributes']['email1'])) {
            $contributor->email = $boondResource['attributes']['email1'];
        }
    }

    /**
     * @param array<string, mixed> $boondProject
     */
    private function createProjectFromBoond(array $boondProject, Company $company): Project
    {
        $project                 = new Project();
        $project->company        = $company;
        $project->boondManagerId = (int) $boondProject['id'];
        $project->name           = $boondProject['attributes']['name'] ?? 'Projet Boond #'.$boondProject['id'];
        $project->status         = 'active';
        $project->projectType    = 'forfait';

        if (isset($boondProject['attributes']['startDate'])) {
            $project->startDate = new DateTime($boondProject['attributes']['startDate']);
        }
        if (isset($boondProject['attributes']['endDate'])) {
            $project->endDate = new DateTime($boondProject['attributes']['endDate']);
        }

        $this->em->persist($project);

        return $project;
    }

    /**
     * @param array<string, mixed> $boondProject
     */
    private function updateProjectFromBoond(Project $project, array $boondProject): void
    {
        if (isset($boondProject['attributes']['name'])) {
            $project->name = $boondProject['attributes']['name'];
        }
        if (isset($boondProject['attributes']['startDate'])) {
            $project->startDate = new DateTime($boondProject['attributes']['startDate']);
        }
        if (isset($boondProject['attributes']['endDate'])) {
            $project->endDate = new DateTime($boondProject['attributes']['endDate']);
        }
    }

    /**
     * @param array<string, mixed> $boondTime
     */
    private function extractResourceId(array $boondTime): ?int
    {
        // L'API Boond peut avoir differentes structures
        if (isset($boondTime['relationships']['resource']['data']['id'])) {
            return (int) $boondTime['relationships']['resource']['data']['id'];
        }
        if (isset($boondTime['attributes']['resourceId'])) {
            return (int) $boondTime['attributes']['resourceId'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $boondTime
     */
    private function extractProjectId(array $boondTime): ?int
    {
        if (isset($boondTime['relationships']['project']['data']['id'])) {
            return (int) $boondTime['relationships']['project']['data']['id'];
        }
        if (isset($boondTime['attributes']['projectId'])) {
            return (int) $boondTime['attributes']['projectId'];
        }

        return null;
    }

    /**
     * @param array<string, mixed> $boondTime
     */
    private function extractDate(array $boondTime): ?DateTimeInterface
    {
        $dateStr = $boondTime['attributes']['date'] ?? null;
        if ($dateStr) {
            return new DateTime($dateStr);
        }

        return null;
    }

    /**
     * @param array<string, mixed> $boondTime
     */
    private function extractHours(array $boondTime): ?string
    {
        // Boond peut exprimer le temps en heures ou en jours
        if (isset($boondTime['attributes']['duration'])) {
            return (string) $boondTime['attributes']['duration'];
        }
        if (isset($boondTime['attributes']['hours'])) {
            return (string) $boondTime['attributes']['hours'];
        }
        // Si en jours, convertir en heures (1 jour = 8 heures)
        if (isset($boondTime['attributes']['days'])) {
            return bcmul((string) $boondTime['attributes']['days'], '8', 2);
        }

        return null;
    }
}
