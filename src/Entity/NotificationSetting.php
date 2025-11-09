<?php

namespace App\Entity;

use App\Repository\NotificationSettingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NotificationSettingRepository::class)]
#[ORM\Table(name: 'notification_settings')]
class NotificationSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true)]
    private string $settingKey;

    #[ORM\Column(type: Types::JSON)]
    private array $settingValue = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSettingKey(): string
    {
        return $this->settingKey;
    }

    public function setSettingKey(string $settingKey): self
    {
        $this->settingKey = $settingKey;

        return $this;
    }

    public function getSettingValue(): array
    {
        return $this->settingValue;
    }

    public function setSettingValue(array $settingValue): self
    {
        $this->settingValue = $settingValue;

        return $this;
    }
}
