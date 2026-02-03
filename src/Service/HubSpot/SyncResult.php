<?php

declare(strict_types=1);

namespace App\Service\HubSpot;

/**
 * Resultat d'une operation de synchronisation HubSpot.
 */
class SyncResult
{
    public bool $success = false;
    public ?string $error = null;
    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    /** @var string[] */
    public array $errors = [];

    public function getTotal(): int
    {
        return $this->created + $this->updated + $this->skipped;
    }

    public function getSummary(): string
    {
        if (!$this->success) {
            return sprintf('Echec: %s', $this->error ?? 'Erreur inconnue');
        }

        return sprintf(
            'Succes: %d cree(s), %d mis a jour, %d ignore(s)',
            $this->created,
            $this->updated,
            $this->skipped,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'error' => $this->error,
            'created' => $this->created,
            'updated' => $this->updated,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
            'total' => $this->getTotal(),
            'summary' => $this->getSummary(),
        ];
    }
}
