<?php

declare(strict_types=1);

namespace App\Application\Vacation\Command\CancelVacation;

final readonly class CancelVacationCommand
{
    /**
     * @param int|null $cancelledByUserId Identifiant du User qui annule. NULL = annulation par l'intervenant lui-meme (self-cancel).
     *                                    Sinon = annulation par un manager (US-069), declenche la notification dediee.
     */
    public function __construct(
        public string $vacationId,
        public ?int $cancelledByUserId = null,
    ) {
    }

    public function isManagerInitiated(): bool
    {
        return $this->cancelledByUserId !== null;
    }
}
