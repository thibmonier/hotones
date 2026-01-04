<?php

namespace App\Repository;

use App\Entity\NotificationSetting;
use App\Security\CompanyContext;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends CompanyAwareRepository<NotificationSetting>
 */
class NotificationSettingRepository extends CompanyAwareRepository
{
    // Clés de configuration prédéfinies
    public const KEY_BUDGET_ALERT_THRESHOLD     = 'budget_alert_threshold'; // Pourcentage (défaut: 80)
    public const KEY_PAYMENT_DUE_DAYS           = 'payment_due_days'; // Nombre de jours (défaut: 7)
    public const KEY_WEBHOOK_URL                = 'webhook_url'; // URL Slack/Discord
    public const KEY_WEBHOOK_TOKEN              = 'webhook_token'; // Token d'authentification
    public const KEY_TIMESHEET_WEEKLY_TOLERANCE = 'timesheet_weekly_tolerance'; // Tolérance (0.15 = 15%)

    public function __construct(
        ManagerRegistry $registry,
        CompanyContext $companyContext
    ) {
        parent::__construct($registry, NotificationSetting::class, $companyContext);
    }

    /**
     * Récupère une valeur de configuration.
     */
    public function getValue(string $key, mixed $default = null): mixed
    {
        $setting = $this->findOneBy(['settingKey' => $key]);

        if ($setting === null) {
            return $default;
        }

        $value = $setting->getSettingValue();

        // Si c'est un tableau avec une seule clé "value", on retourne directement la valeur
        if (isset($value['value'])) {
            return $value['value'];
        }

        return $value;
    }

    /**
     * Définit ou met à jour une valeur de configuration.
     */
    public function setValue(string $key, mixed $value): void
    {
        $setting = $this->findOneBy(['settingKey' => $key]);

        if ($setting === null) {
            $setting = new NotificationSetting();
            $setting->setSettingKey($key);
        }

        // On stocke dans un tableau avec clé "value" pour uniformiser
        $setting->setSettingValue(['value' => $value]);

        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();
    }

    /**
     * Récupère toutes les configurations sous forme de tableau associatif.
     *
     * @return array<string, mixed>
     */
    public function getAllAsArray(): array
    {
        $settings = $this->findAll();
        $result   = [];

        foreach ($settings as $setting) {
            $value                             = $setting->getSettingValue();
            $result[$setting->getSettingKey()] = $value['value'] ?? $value;
        }

        return $result;
    }

    /**
     * Initialise les valeurs par défaut si elles n'existent pas.
     */
    public function initializeDefaults(): void
    {
        $defaults = [
            self::KEY_BUDGET_ALERT_THRESHOLD     => 80,
            self::KEY_PAYMENT_DUE_DAYS           => 7,
            self::KEY_TIMESHEET_WEEKLY_TOLERANCE => 0.15,
        ];

        foreach ($defaults as $key => $value) {
            if ($this->findOneBy(['settingKey' => $key]) === null) {
                $this->setValue($key, $value);
            }
        }
    }
}
