# Commandes CLI

Ce document liste toutes les commandes CLI disponibles dans le projet HotOnes.

## Table des matières

- [Gestion des utilisateurs](#gestion-des-utilisateurs)
- [Calcul des métriques](#calcul-des-métriques)
- [Génération de données de test](#génération-de-données-de-test)
- [Notifications](#notifications)
- [Debug](#debug)

---

## Gestion des utilisateurs

### `app:user:create`

Crée un nouvel utilisateur avec un contributeur associé.

**Syntaxe:**
```bash
php bin/console app:user:create <email> <password> <firstName> <lastName>
```

**Arguments:**
- `email` (requis): Adresse email de l'utilisateur
- `password` (requis): Mot de passe en clair (sera hashé automatiquement)
- `firstName` (requis): Prénom de l'utilisateur
- `lastName` (requis): Nom de famille de l'utilisateur

**Comportement:**
- Crée un utilisateur avec le rôle `ROLE_USER`
- Crée automatiquement un contributeur lié (actif par défaut)
- Hash le mot de passe avec l'algorithme configuré

**Exemple:**
```bash
php bin/console app:user:create jean.dupont@example.com "SecurePass123" Jean Dupont
```

**Sortie:**
```
User created: jean.dupont@example.com (Contributor #42)
```

---

## Calcul des métriques

### `app:calculate-metrics`

Calcule les métriques analytics (CA, marges, KPIs) pour une période donnée.

**Syntaxe:**
```bash
php bin/console app:calculate-metrics [period] [options]
```

**Arguments:**
- `period` (optionnel): Période à calculer
  - Format année: `YYYY` (ex: `2024`)
  - Format mois: `YYYY-MM` (ex: `2024-03`)
  - Par défaut: année courante

**Options:**
- `--granularity=<value>` ou `-g <value>`: Granularité du calcul
  - Valeurs possibles: `monthly`, `quarterly`, `yearly`
  - Par défaut: `monthly`
- `--force-recalculate` ou `-f`: Force le re-calcul complet de l'année (ignore les données existantes)

**Exemples:**

Calculer les métriques mensuelles pour l'année 2024:
```bash
php bin/console app:calculate-metrics 2024
```

Calculer les métriques pour mars 2024 uniquement:
```bash
php bin/console app:calculate-metrics 2024-03
```

Calculer les métriques trimestrielles pour 2024:
```bash
php bin/console app:calculate-metrics 2024 --granularity=quarterly
```

Forcer le re-calcul complet de 2024:
```bash
php bin/console app:calculate-metrics 2024 --force-recalculate
```

**Métriques calculées:**
- Nombre de projets (total, actifs, terminés)
- Nombre de devis (total, en attente, signés)
- Chiffre d'affaires (réalisé et prévisionnel)
- Coûts et achats
- Marges (brute et nette, en € et %)
- Jours vendus et travaillés
- Taux d'occupation
- Valeur moyenne des devis

**Automatisation (cron):**
```cron
# Calcul quotidien à 6h du matin
0 6 * * * cd /path/to/hotones && php bin/console app:calculate-metrics
```

---

### `app:calculate-staffing-metrics`

Calcule les métriques de staffing (taux de staffing et TACE) pour une période donnée.

**Syntaxe:**
```bash
php bin/console app:calculate-staffing-metrics [period] [options]
```

**Arguments:**
- `period` (optionnel): Période à calculer
  - Format année: `YYYY`
  - Format mois: `YYYY-MM`
  - Par défaut: année courante

**Options:**
- `--granularity=<value>` ou `-g <value>`: Granularité du calcul
  - Valeurs possibles: `monthly`, `quarterly`, `weekly`
  - Par défaut: `monthly`
- `--force-recalculate` ou `-f`: Force le re-calcul même si les données existent déjà
- `--range=<months>` ou `-r <months>`: Range en mois à calculer (utilisé si aucune période spécifiée)
  - Par défaut: `12` (12 derniers mois)

**Exemples:**

Calculer le staffing pour l'année 2024:
```bash
php bin/console app:calculate-staffing-metrics 2024
```

Calculer le staffing pour mars 2024:
```bash
php bin/console app:calculate-staffing-metrics 2024-03
```

Calculer le staffing hebdomadaire pour 2024:
```bash
php bin/console app:calculate-staffing-metrics 2024 --granularity=weekly
```

Calculer le staffing pour les 6 derniers mois:
```bash
php bin/console app:calculate-staffing-metrics --range=6
```

Forcer le re-calcul:
```bash
php bin/console app:calculate-staffing-metrics 2024 --force-recalculate
```

**Métriques calculées:**
- Taux de staffing par contributeur
- TACE (Taux d'Activité Contributeur Effectif)
- Heures planifiées vs heures réelles
- Disponibilité et charge de travail

**Automatisation (cron):**
```cron
# Calcul quotidien à 6h du matin pour les 12 derniers mois
0 6 * * * cd /path/to/hotones && php bin/console app:calculate-staffing-metrics --range=12
```

---

### `app:metrics:dispatch`

Dispatche les calculs de métriques en asynchrone via Symfony Messenger. Utile pour calculer une grande quantité de périodes sans bloquer le terminal.

**Syntaxe:**
```bash
php bin/console app:metrics:dispatch [options]
```

**Options:**
- `--year=<YYYY>`: Dispatche le calcul pour toute l'année (mensuel, trimestriel et annuel)
- `--date=<Y-m-d>`: Dispatche le calcul pour une date spécifique
- `--granularity=<value>`: Granularité (utilisé avec `--date`)
  - Valeurs possibles: `monthly`, `quarterly`, `yearly`
  - Par défaut: `monthly`

**Exemples:**

Dispatcher tous les calculs pour 2024 (17 jobs: 12 mois + 4 trimestres + 1 année):
```bash
php bin/console app:metrics:dispatch --year=2024
```

Dispatcher le calcul mensuel pour janvier 2024:
```bash
php bin/console app:metrics:dispatch --date=2024-01-01 --granularity=monthly
```

Dispatcher le calcul trimestriel pour Q1 2024:
```bash
php bin/console app:metrics:dispatch --date=2024-01-01 --granularity=quarterly
```

**Note importante:**
- Cette commande dispatche les jobs dans la file d'attente Messenger
- Les workers Messenger doivent être actifs pour traiter les jobs:
  ```bash
  php bin/console messenger:consume async -vv
  ```
- Voir la documentation [Worker Operations](worker-operations.md) pour plus de détails

---

## Génération de données de test

### `app:generate-test-data`

Génère des données de test fictives pour le dashboard analytics. **Environnement dev/test uniquement**.

**Syntaxe:**
```bash
php bin/console app:generate-test-data [options]
```

**Options:**
- `--year=<YYYY>` ou `-y <YYYY>`: Année pour laquelle générer les données
  - Par défaut: année courante
- `--force` ou `-f`: Force la suppression des données existantes avant génération

**Exemples:**

Générer des données de test pour l'année courante:
```bash
php bin/console app:generate-test-data
```

Générer des données de test pour 2023:
```bash
php bin/console app:generate-test-data --year=2023
```

Régénérer les données de test (supprime puis recrée):
```bash
php bin/console app:generate-test-data --force
```

**Données générées:**
- Dimensions temporelles (12 mois de l'année)
- Types de projets variés (forfait/régie, e-commerce/brand, internes/externes)
- Contributeurs fictifs (chefs de projet, commerciaux, directeurs)
- Métriques réalistes avec variations saisonnières (moins d'activité en août et décembre)

**Avertissement:**
Ne jamais utiliser cette commande en production ! Elle est uniquement destinée au développement et aux tests.

---

### `app:seed-projects-2025`

Seed de projets pour l'année 2025.

**Syntaxe:**
```bash
php bin/console app:seed-projects-2025
```

**Note:** Commande spécifique pour initialiser des projets de test pour 2025.

---

### `app:create-test-subtasks`

Création de sous-tâches de test.

**Syntaxe:**
```bash
php bin/console app:create-test-subtasks
```

**Note:** Commande de test pour créer des sous-tâches fictives.

---

## Notifications

### `app:notify:timesheets-weekly`

Envoie les notifications hebdomadaires aux contributeurs n'ayant pas saisi suffisamment d'heures sur la semaine en cours (avec tolérance configurable).

**Syntaxe:**
```bash
php bin/console app:notify:timesheets-weekly
```

**Comportement:**
- Vérifie tous les contributeurs actifs
- Calcule les heures attendues selon leur contrat (EmploymentPeriod)
- Vérifie les heures saisies du lundi au vendredi midi de la semaine courante
- Applique une tolérance de 15% par défaut (configurable via NotificationSetting)
- Envoie une notification in-app si heures insuffisantes
- Envoie un email si l'utilisateur a activé les notifications email

**Exemple de notification:**
```
Rappel de saisie des temps
Vous avez saisi 28.5 h sur 35.0 h attendues cette semaine (tolérance 15%).
Merci de compléter vos temps.
```

**Configuration recommandée (cron):**
```cron
# Exécution le vendredi à 12h (midi)
0 12 * * 5 cd /path/to/hotones && php bin/console app:notify:timesheets-weekly
```

**Paramètres configurables:**
- Tolérance (défaut 15%): Via `NotificationSetting::KEY_TIMESHEET_WEEKLY_TOLERANCE`
- Préférences email par utilisateur: Entity `NotificationPreference`

**Voir aussi:**
- [Documentation Notifications](notifications.md)
- [Documentation Time Planning](time-planning.md)

---

## Debug

### `app:debug-task-assignment`

Commande de debug pour analyser les assignations de tâches.

**Syntaxe:**
```bash
php bin/console app:debug-task-assignment
```

**Usage:**
Utile pour diagnostiquer les problèmes d'assignation de tâches aux contributeurs.

---

## Bonnes pratiques

### Exécution en arrière-plan

Pour les commandes de calcul longues, utilisez `nohup` ou `screen`:

```bash
# Avec nohup
nohup php bin/console app:calculate-metrics 2024 > /tmp/metrics-2024.log 2>&1 &

# Avec screen
screen -S metrics
php bin/console app:calculate-metrics 2024
# Ctrl+A puis D pour détacher
```

### Mode verbeux

Toutes les commandes Symfony supportent les options de verbosité:

```bash
# Verbosité normale
php bin/console app:calculate-metrics

# Mode verbose
php bin/console app:calculate-metrics -v

# Mode très verbose
php bin/console app:calculate-metrics -vv

# Mode debug
php bin/console app:calculate-metrics -vvv
```

### Environnement

Spécifier l'environnement explicitement:

```bash
# Environnement dev (par défaut)
php bin/console app:calculate-metrics --env=dev

# Environnement prod
php bin/console app:calculate-metrics --env=prod

# Environnement test
php bin/console app:calculate-metrics --env=test
```

### Automatisation recommandée

Configuration cron complète pour un environnement de production:

```cron
# Calcul des métriques analytics quotidien à 6h
0 6 * * * cd /var/www/hotones && php bin/console app:calculate-metrics --env=prod

# Calcul des métriques de staffing quotidien à 6h15
15 6 * * * cd /var/www/hotones && php bin/console app:calculate-staffing-metrics --range=12 --env=prod

# Rappel hebdomadaire de saisie des temps (vendredi 12h)
0 12 * * 5 cd /var/www/hotones && php bin/console app:notify:timesheets-weekly --env=prod
```

---

## Voir aussi

- [Worker Operations](worker-operations.md) - Workers Messenger et traitement asynchrone
- [Analytics](analytics.md) - Dashboard et métriques
- [Notifications](notifications.md) - Système de notifications
- [Time Planning](time-planning.md) - Gestion du temps et planning
