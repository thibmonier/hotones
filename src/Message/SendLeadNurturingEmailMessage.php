<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message pour dispatcher l'envoi d'emails de nurturing aux leads.
 */
final readonly class SendLeadNurturingEmailMessage
{
    public function __construct(
        private int $leadId,
        private int $dayNumber,
    ) {
    }

    public function getLeadId(): int
    {
        return $this->leadId;
    }

    public function getDayNumber(): int
    {
        return $this->dayNumber;
    }
}
