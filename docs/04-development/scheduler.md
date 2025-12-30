# Symfony Scheduler

Le Symfony Scheduler permet de planifier l'exécution de tâches récurrentes directement depuis la configuration Symfony, en remplacement ou complément des crons traditionnels.

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Configuration](#configuration)
- [Expressions de planification](#expressions-de-planification)
- [Tâches recommandées pour HotOnes](#tâches-recommandées-pour-hotones)
- [Exécution du scheduler](#exécution-du-scheduler)
- [Messages vs Commandes](#messages-vs-commandes)
- [Monitoring](#monitoring)
- [Migration depuis Cron](#migration-depuis-cron)
- [Bonnes pratiques](#bonnes-pratiques)

---

## Vue d'ensemble

**Symfony Scheduler** est activé dans le projet mais non configuré actuellement.

**Fichier:** `config/packages/scheduler.yaml`

**État actuel:**
```yaml
framework:
  scheduler:
    enabled: true
    # Tout le reste est commenté
```

**Avantages vs Cron:**
- Configuration centralisée dans Symfony
- Expressions plus lisibles (frequency objects)
- Meilleur logging et monitoring
- Dispatch de messages Messenger (asynchrone)
- Testable

**Inconvénient:**
- Nécessite un worker Scheduler actif (comme Messenger)

---

## Configuration

### Configuration de base

**Fichier:** `config/packages/scheduler.yaml`

```yaml
framework:
  scheduler:
    enabled: true
    schedules:
      default:
        # Fuseau horaire pour toutes les tâches
        timezone: 'Europe/Paris'

        tasks:
          # Vos tâches ici
```

---

### Types de tâches

**1. Message Messenger (recommandé):**
```yaml
tasks:
  recalculate_metrics:
    message: App\Message\RecalculateMetricsMessage
    frequency: '0 6 * * *'  # Tous les jours à 6h
    arguments: ['2024-01-01', 'monthly']
```

**2. Commande Symfony:**
```yaml
tasks:
  cleanup_notifications:
    command: 'app:notifications:cleanup'
    frequency: '0 3 * * *'  # Tous les jours à 3h
```

**3. Callback (PHP):**
```yaml
tasks:
  custom_task:
    callback: ['App\Service\MyService', 'myMethod']
    frequency: '*/15 * * * *'  # Toutes les 15 minutes
```

---

## Expressions de planification

### Format Cron standard

```yaml
# Format: minute heure jour mois jour_semaine
# Exemples:
'0 6 * * *'          # Tous les jours à 6h
'0 */6 * * *'        # Toutes les 6 heures
'0 0 * * 0'          # Tous les dimanches à minuit
'0 12 * * 5'         # Tous les vendredis à midi
'*/15 * * * *'       # Toutes les 15 minutes
'0 0 1 * *'          # Le 1er de chaque mois à minuit
'0 0 1 1 *'          # Le 1er janvier à minuit
```

### Objets Frequency (Symfony 6.3+)

**Plus lisible et expressif:**

```yaml
tasks:
  daily_task:
    message: App\Message\DailyTaskMessage
    frequency: daily  # Tous les jours à minuit

  hourly_task:
    message: App\Message\HourlyTaskMessage
    frequency: hourly  # Toutes les heures

  weekly_task:
    message: App\Message\WeeklyTaskMessage
    frequency:
      weekdays: monday
      time: '09:00'
```

**Expressions avancées:**
```yaml
# Plusieurs jours de la semaine
frequency:
  weekdays: [monday, wednesday, friday]
  time: '14:00'

# Plusieurs fois par jour
frequency:
  times: ['06:00', '12:00', '18:00']

# Premier jour du mois
frequency:
  day: 1
  time: '00:00'
```

---

## Tâches recommandées pour HotOnes

### Configuration complète suggérée

```yaml
# config/packages/scheduler.yaml
framework:
  scheduler:
    enabled: true
    schedules:
      default:
        timezone: 'Europe/Paris'

        tasks:
          # Calcul des métriques analytics quotidien
          calculate_analytics_metrics:
            command: 'app:calculate-metrics'
            frequency: '0 6 * * *'
            description: 'Calcul quotidien des métriques analytics'

          # Calcul des métriques de staffing
          calculate_staffing_metrics:
            command: 'app:calculate-staffing-metrics --range=12'
            frequency: '15 6 * * *'
            description: 'Calcul des métriques de staffing (12 derniers mois)'

          # Rappel hebdomadaire de saisie des temps
          notify_weekly_timesheets:
            command: 'app:notify:timesheets-weekly'
            frequency: '0 12 * * 5'  # Vendredi 12h
            description: 'Rappel hebdomadaire de saisie des temps'

          # Nettoyage des anciennes notifications
          cleanup_old_notifications:
            command: 'app:notifications:cleanup'
            frequency: '0 3 * * *'
            description: 'Nettoyage des notifications lues de plus de 30 jours'

          # Nettoyage du cache (optionnel)
          clear_cache_pools:
            command: 'cache:pool:clear cache.global_clearer'
            frequency: '0 4 * * *'
            description: 'Nettoyage des pools de cache'

          # Backup automatique (si implémenté)
          # backup_database:
          #   callback: ['App\Service\BackupService', 'backupDatabase']
          #   frequency: '0 2 * * *'
          #   description: 'Sauvegarde quotidienne de la base de données'
```

---

### Tâches avec Messages (asynchrone recommandé)

**Avantage:** Exécution asynchrone via Messenger, ne bloque pas le scheduler.

```yaml
tasks:
  # Dispatch d'un message pour calcul asynchrone
  dispatch_metrics_calculation:
    message: App\Message\RecalculateMetricsMessage
    frequency: '0 6 * * *'
    arguments:
      - !php/const App\Message\RecalculateMetricsMessage::GRANULARITY_MONTHLY
```

**Créer le message si besoin:**
```php
// src/Message/RecalculateMetricsMessage.php
namespace App\Message;

class RecalculateMetricsMessage
{
    public const GRANULARITY_MONTHLY = 'monthly';
    public const GRANULARITY_QUARTERLY = 'quarterly';

    public function __construct(
        public readonly string $granularity = self::GRANULARITY_MONTHLY
    ) {
    }
}
```

**Handler:**
```php
// src/MessageHandler/RecalculateMetricsMessageHandler.php
namespace App\MessageHandler;

use App\Message\RecalculateMetricsMessage;
use App\Service\Analytics\MetricsCalculationService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class RecalculateMetricsMessageHandler
{
    public function __construct(
        private readonly MetricsCalculationService $metricsService
    ) {
    }

    public function __invoke(RecalculateMetricsMessage $message): void
    {
        $date = new \DateTime();
        $this->metricsService->calculateMetricsForPeriod($date, $message->granularity);
    }
}
```

---

## Exécution du scheduler

### Mode développement

**Lancer le scheduler worker:**
```bash
docker-compose exec app php bin/console messenger:consume scheduler_default -vv
```

**Lister les tâches planifiées:**
```bash
docker-compose exec app php bin/console debug:scheduler
```

**Tester une tâche immédiatement:**
```bash
docker-compose exec app php bin/console scheduler:run
```

---

### Mode production (Supervisor)

**Fichier:** `/etc/supervisor/conf.d/hotones-scheduler.conf`

```ini
[program:hotones-scheduler]
command=/usr/bin/php /var/www/hotones/bin/console messenger:consume scheduler_default --time-limit=3600 -vv
user=hotones
numprocs=1
startsecs=0
autostart=true
autorestart=true
stdout_logfile=/var/www/hotones/var/log/scheduler.log
stderr_logfile=/var/www/hotones/var/log/scheduler_error.log
```

**Activer:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start hotones-scheduler

# Vérifier
sudo supervisorctl status
```

---

### Alternative: Cron pour lancer le scheduler

Si vous ne voulez pas un worker permanent, utiliser un cron qui exécute `scheduler:run`:

```cron
# Exécute toutes les minutes pour vérifier les tâches à lancer
* * * * * cd /var/www/hotones && php bin/console scheduler:run >> /dev/null 2>&1
```

**Note:** Cette approche est moins efficace qu'un worker dédié.

---

## Messages vs Commandes

### Quand utiliser des Messages

**Avantages:**
- Exécution asynchrone (non-bloquant)
- Retry automatique en cas d'échec
- Peut être distribué sur plusieurs workers

**Cas d'usage:**
- Tâches longues (calculs, exports)
- Tâches avec dépendances externes (APIs, emails)
- Tâches critiques nécessitant retry

**Exemple:**
```yaml
dispatch_heavy_computation:
  message: App\Message\HeavyComputationMessage
  frequency: '0 2 * * *'
```

---

### Quand utiliser des Commandes

**Avantages:**
- Plus simple si la commande existe déjà
- Exécution synchrone (garantie d'ordre)
- Logs directs

**Cas d'usage:**
- Tâches rapides (nettoyage, purge)
- Tâches séquentielles
- Commandes existantes sans Message associé

**Exemple:**
```yaml
cleanup_cache:
  command: 'cache:clear --env=prod'
  frequency: '0 4 * * *'
```

---

## Monitoring

### Logs du scheduler

**Développement:**
```bash
docker-compose exec app php bin/console messenger:consume scheduler_default -vv
# Mode très verbeux pour voir chaque tâche
```

**Production:**
```bash
# Logs Supervisor
tail -f /var/www/hotones/var/log/scheduler.log

# Logs applicatifs
tail -f /var/www/hotones/var/log/prod.log | grep Scheduler
```

---

### Vérifier les tâches planifiées

```bash
# Lister toutes les tâches
php bin/console debug:scheduler

# Exemple de sortie:
# default schedule
# ================
#
# calculate_analytics_metrics
# ---------------------------
# Frequency: 0 6 * * *
# Next Run: 2024-03-16 06:00:00
# Command: app:calculate-metrics
```

---

### Alertes en cas d'échec

**Créer un EventSubscriber:**

```php
// src/EventSubscriber/SchedulerFailureSubscriber.php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Scheduler\Event\FailureEvent;
use Psr\Log\LoggerInterface;

class SchedulerFailureSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger,
        // private readonly MailerInterface $mailer  // Pour notifier par email
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FailureEvent::class => 'onSchedulerFailure',
        ];
    }

    public function onSchedulerFailure(FailureEvent $event): void
    {
        $this->logger->critical('Scheduler task failed', [
            'task' => $event->getTask()->getName(),
            'exception' => $event->getThrowable()->getMessage(),
        ]);

        // Optionnel: Envoyer un email d'alerte
        // $this->mailer->send(...);
    }
}
```

---

## Migration depuis Cron

### Avant (Cron classique)

```cron
# crontab -e
0 6 * * * cd /var/www/hotones && php bin/console app:calculate-metrics
15 6 * * * cd /var/www/hotones && php bin/console app:calculate-staffing-metrics --range=12
0 12 * * 5 cd /var/www/hotones && php bin/console app:notify:timesheets-weekly
0 3 * * * cd /var/www/hotones && php bin/console app:notifications:cleanup
```

---

### Après (Symfony Scheduler)

**1. Configuration scheduler.yaml (comme ci-dessus)**

**2. Démarrer le worker Scheduler (Supervisor)**

**3. Supprimer les crons (optionnel):**
```bash
crontab -e
# Supprimer les lignes ou les commenter
```

---

### Approche hybride (recommandée au départ)

Garder le cron principal qui lance `scheduler:run`:

```cron
# Cron minimal: vérifie toutes les minutes si des tâches doivent être lancées
* * * * * cd /var/www/hotones && php bin/console scheduler:run >> /var/log/scheduler.log 2>&1
```

**Avantages:**
- Pas besoin de worker Supervisor dédié
- Compatible avec infrastructure existante
- Transition en douceur

**Inconvénient:**
- Moins efficace qu'un worker permanent
- Tâches synchrones (bloquantes)

---

## Bonnes pratiques

### 1. Toujours définir une description

```yaml
tasks:
  my_task:
    command: 'app:my-command'
    frequency: '0 6 * * *'
    description: 'Description claire de ce que fait la tâche'
```

---

### 2. Utiliser des Messages pour les tâches longues

```yaml
# ✅ Bon (asynchrone)
heavy_task:
  message: App\Message\HeavyTaskMessage
  frequency: daily

# ❌ Éviter (bloquant)
heavy_task:
  command: 'app:heavy-task'
  frequency: daily
```

---

### 3. Configurer le fuseau horaire

```yaml
schedules:
  default:
    timezone: 'Europe/Paris'  # Important pour cohérence
```

---

### 4. Grouper les tâches par schedule si besoin

```yaml
schedules:
  # Tâches critiques
  critical:
    timezone: 'Europe/Paris'
    tasks:
      calculate_metrics: { ... }

  # Tâches de maintenance
  maintenance:
    timezone: 'Europe/Paris'
    tasks:
      cleanup_old_data: { ... }
```

---

### 5. Logger les exécutions

Dans vos handlers/commandes:

```php
$this->logger->info('Scheduled task started', [
    'task' => 'calculate_metrics',
    'timestamp' => new \DateTime(),
]);

// ... traitement

$this->logger->info('Scheduled task completed', [
    'task' => 'calculate_metrics',
    'duration' => $duration,
]);
```

---

### 6. Gérer les erreurs gracieusement

```php
try {
    $this->metricsService->calculate();
} catch (\Exception $e) {
    $this->logger->error('Scheduled task failed', [
        'task' => 'calculate_metrics',
        'error' => $e->getMessage(),
    ]);

    // Ne pas re-throw si vous voulez que le scheduler continue
    // throw $e;  // Re-throw si vous voulez retry (Messenger)
}
```

---

## Exemple de configuration complète pour HotOnes

**Fichier final recommandé:**

```yaml
# config/packages/scheduler.yaml
framework:
  scheduler:
    enabled: true
    schedules:
      default:
        timezone: 'Europe/Paris'

        tasks:
          # Analytics
          calculate_analytics_metrics:
            command: 'app:calculate-metrics'
            frequency: '0 6 * * *'
            description: 'Calcul quotidien des métriques analytics à 6h'

          calculate_staffing_metrics:
            command: 'app:calculate-staffing-metrics --range=12'
            frequency: '15 6 * * *'
            description: 'Calcul des métriques de staffing à 6h15'

          # Notifications
          notify_weekly_timesheets:
            command: 'app:notify:timesheets-weekly'
            frequency: '0 12 * * 5'
            description: 'Rappel hebdomadaire de saisie des temps (vendredi 12h)'

          # Maintenance
          cleanup_old_notifications:
            command: 'app:notifications:cleanup'
            frequency: '0 3 * * *'
            description: 'Nettoyage des notifications lues anciennes à 3h'

          clear_cache:
            command: 'cache:pool:clear cache.global_clearer'
            frequency: '0 4 * * *'
            description: 'Nettoyage du cache à 4h'
```

---

## Commandes utiles

```bash
# Lister les tâches planifiées
php bin/console debug:scheduler

# Exécuter immédiatement toutes les tâches dues
php bin/console scheduler:run

# Lancer le worker scheduler (mode service)
php bin/console messenger:consume scheduler_default -vv

# Tester le scheduler sans vraiment exécuter
php bin/console scheduler:list
```

---

## Voir aussi

- [Commands](commands.md) - Commandes à planifier
- [Deployment](deployment.md) - Configuration Supervisor en production
- [Notifications](notifications.md) - Notifications planifiées
- [Symfony Scheduler Documentation](https://symfony.com/doc/current/scheduler.html)
