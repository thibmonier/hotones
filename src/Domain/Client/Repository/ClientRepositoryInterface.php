<?php

declare(strict_types=1);

namespace App\Domain\Client\Repository;

use App\Domain\Client\Entity\Client;
use App\Domain\Client\Exception\ClientNotFoundException;
use App\Domain\Client\ValueObject\ClientId;
use App\Domain\Shared\ValueObject\Email;

interface ClientRepositoryInterface
{
    /**
     * @throws ClientNotFoundException
     */
    public function findById(ClientId $id): Client;

    public function findByIdOrNull(ClientId $id): ?Client;

    public function findByEmail(Email $email): ?Client;

    /**
     * @return array<Client>
     */
    public function findAll(): array;

    /**
     * @return array<Client>
     */
    public function findActive(): array;

    public function save(Client $client): void;

    public function delete(Client $client): void;
}
