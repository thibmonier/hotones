<?php

declare(strict_types=1);

namespace App\Scheduler;

use App\Message\ProcessNurturingEmailsMessage;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;

/**
 * Provider de schedules pour les tâches de lead magnet (nurturing emails).
 */
#[AsSchedule('lead_magnet')]
class LeadMagnetScheduleProvider implements ScheduleProviderInterface
{
    public function getSchedule(): Schedule
    {
        return (new Schedule())
            // Envoi quotidien des emails de nurturing à 9h du matin
            // Cela donne le temps aux leads de télécharger le guide avant le premier email
            ->add(
                RecurringMessage::cron('0 9 * * *', new ProcessNurturingEmailsMessage()),
            )
        ;
    }
}
