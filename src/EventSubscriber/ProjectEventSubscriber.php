<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use App\Entity\Project;
use App\Entity\ProjectEvent;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
class ProjectEventSubscriber
{
    public function __construct(
        private Security $security,
        private EntityManagerInterface $em
    ) {
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Project) {
            return;
        }

        $this->createEvent(
            $entity,
            'project_created',
            sprintf('Projet "%s" créé', $entity->getName())
        );
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Project) {
            return;
        }

        $changeSet = $this->em->getUnitOfWork()->getEntityChangeSet($entity);

        // Tracer les changements de statut
        if (isset($changeSet['status'])) {
            [$oldStatus, $newStatus] = $changeSet['status'];
            $this->createEvent(
                $entity,
                'status_changed',
                sprintf('Statut changé de "%s" à "%s"', $oldStatus, $newStatus),
                ['old' => $oldStatus, 'new' => $newStatus]
            );
        }

        // Tracer les changements de dates
        if (isset($changeSet['startDate']) || isset($changeSet['endDate'])) {
            $this->createEvent(
                $entity,
                'dates_updated',
                'Dates du projet modifiées'
            );
        }

        // Tracer les changements de budget
        if (isset($changeSet['estimatedBudget'])) {
            [$oldBudget, $newBudget] = $changeSet['estimatedBudget'];
            $this->createEvent(
                $entity,
                'budget_updated',
                sprintf('Budget modifié de %s€ à %s€', $oldBudget ?? '0', $newBudget ?? '0'),
                ['old' => $oldBudget, 'new' => $newBudget]
            );
        }

        // Tracer les changements de chef de projet
        if (isset($changeSet['projectManager'])) {
            [$oldPm, $newPm] = $changeSet['projectManager'];
            $oldName = $oldPm ? $oldPm->getFullName() : 'Aucun';
            $newName = $newPm ? $newPm->getFullName() : 'Aucun';
            $this->createEvent(
                $entity,
                'manager_changed',
                sprintf('Chef de projet changé de "%s" à "%s"', $oldName, $newName),
                ['old' => $oldName, 'new' => $newName]
            );
        }
    }

    private function createEvent(Project $project, string $type, string $description, ?array $data = null): void
    {
        $event = new ProjectEvent();
        $event->setProject($project);
        $event->setEventType($type);
        $event->setDescription($description);
        $event->setData($data);
        $event->setActor($this->security->getUser());

        $this->em->persist($event);
        $this->em->flush();
    }
}
