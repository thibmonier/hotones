# 🔗 Intégration Planning → Staffing & TACE

## 📋 Vue d'ensemble

Les modifications dans la vue Planning impactent désormais la vue du Staffing et du TACE. Les planifications futures sont maintenant incluses dans le calcul des métriques de staffing.

## ✨ Fonctionnalités

### Calcul des jours planifiés

Le service `StaffingMetricsCalculationService` calcule désormais les jours planifiés (`plannedDays`) à partir de l'entité `Planning` :

- **Statuts pris en compte** : `planned` et `confirmed` (les planifications `cancelled` sont ignorées)
- **Gestion du temps partiel** : Les heures quotidiennes (`dailyHours`) sont prises en compte
  - Si `dailyHours = 8h` → 1 jour par jour ouvré
  - Si `dailyHours = 4h` → 0.5 jour par jour ouvré
- **Calcul sur la période** : Seuls les jours ouvrés (hors week-ends) dans l'intersection entre la planification et la période sont comptabilisés

### Impact sur les KPIs

#### Taux d'occupation (Vue annuelle)

Dans la vue annuelle du dashboard (`/staffing/dashboard?view=annual`), le taux d'occupation par semaine inclut maintenant les jours planifiés :

```
Taux d'occupation = (Jours staffés + Jours planifiés) / Jours disponibles × 100
```

**Exemple** :
- Jours disponibles : 5j
- Jours staffés (temps passé réel) : 3j
- Jours planifiés (affectations futures) : 1.5j
- **Taux d'occupation** : (3 + 1.5) / 5 × 100 = **90%**

#### Métriques dans le dashboard

Le champ `plannedDays` est visible dans :
- Les **tooltips** de la vue annuelle (survol des badges de taux d'occupation)
- Les **données brutes** stockées dans `fact_staffing_metrics`

## 🔧 Implémentation technique

### Nouvelle méthode

`StaffingMetricsCalculationService::calculatePlannedDays()`

```php
private function calculatePlannedDays(
    Contributor $contributor,
    DateTimeInterface $start,
    DateTimeInterface $end
): float
```

Cette méthode :
1. Récupère toutes les planifications du contributeur sur la période
2. Filtre par statut (`planned` ou `confirmed`)
3. Calcule l'intersection entre chaque planning et la période
4. Compte les jours ouvrés dans cette intersection
5. Ajuste selon les heures quotidiennes (`dailyHours / 8.0`)

### Requête de planifications

```php
$plannings = $this->entityManager->getRepository(\App\Entity\Planning::class)
    ->createQueryBuilder('p')
    ->where('p.contributor = :contributor')
    ->andWhere('p.startDate <= :end')
    ->andWhere('p.endDate >= :start')
    ->andWhere('p.status IN (:statuses)')
    ->setParameter('contributor', $contributor)
    ->setParameter('start', $start)
    ->setParameter('end', $end)
    ->setParameter('statuses', ['planned', 'confirmed'])
    ->getQuery()
    ->getResult();
```

## 🚀 Utilisation

### Recalcul des métriques

Après avoir ajouté ou modifié des planifications, recalculez les métriques :

```bash
# Dans le conteneur Docker
docker compose exec app php bin/console app:calculate-staffing-metrics --range=12 --force-recalculate

# Ou pour une période spécifique
docker compose exec app php bin/console app:calculate-staffing-metrics 2025 --force-recalculate
```

### Visualisation

1. **Vue standard** (`/staffing/dashboard`) :
   - Graphiques d'évolution du taux de staffing et TACE
   - Tableaux par profil et contributeur

2. **Vue annuelle** (`/staffing/dashboard?view=annual`) :
   - **Matrice hebdomadaire** : taux d'occupation par contributeur et par semaine
   - **Tooltips détaillés** : staffé, planifié, disponible, capacité restante
   - **Codes couleur** :
     - 🔴 Rouge (>100%) : Surcharge
     - 🟠 Orange (90-100%) : Proche capacité max
     - 🟢 Vert (70-89%) : Bonne utilisation
     - ⚪ Gris (<70%) : Sous-utilisation

## 📊 Données exemple

### Avant l'intégration

```
staffedDays: 15.5
plannedDays: 0.0
availableDays: 22.0
→ Taux de staffing: 70.5%
```

### Après l'intégration

```
staffedDays: 15.5
plannedDays: 4.5
availableDays: 22.0
→ Taux de staffing: 70.5% (basé sur staffedDays)
→ Taux d'occupation: 90.9% (staffed + planned)
```

## 👥 Contributeurs sans période d'emploi

Le service calcule désormais les métriques pour **tous les contributeurs actifs**, même ceux qui n'ont pas de période d'emploi (`EmploymentPeriod`) définie.

### Comportement

- **Avec période d'emploi** : Utilise les données réelles (heures hebdomadaires, profils, CJM/TJM)
- **Sans période d'emploi** : Calcule quand même les métriques (temps passés, planifications, congés) mais sans profil associé

### Recommandation

Pour une meilleure précision des métriques, il est recommandé de créer des périodes d'emploi pour tous les contributeurs actifs incluant :
- Heures hebdomadaires de travail
- CJM (Coût Jour Moyen)
- TJM (Tarif Jour Moyen)
- Profils associés

## ⚠️ Points importants

### Distinction entre Taux de Staffing et Taux d'Occupation

- **Taux de Staffing** : Basé uniquement sur les temps **réels** (timesheets)
  - Formule : `(staffedDays / availableDays) × 100`
  - Utilisé pour l'analyse historique

- **Taux d'Occupation** : Inclut les temps réels **et planifiés**
  - Formule : `(staffedDays + plannedDays) / availableDays × 100`
  - Utilisé pour la planification future (vue annuelle)

### Statuts de planification

| Statut | Inclus dans le calcul ? | Description |
|--------|------------------------|-------------|
| `planned` | ✅ Oui | Planification prévisionnelle |
| `confirmed` | ✅ Oui | Planification confirmée |
| `cancelled` | ❌ Non | Planification annulée |

### Calcul automatique

Pour maintenir les métriques à jour, configurez une tâche cron :

```bash
# Tous les jours à 6h du matin
0 6 * * * cd /path/to/project && docker compose exec -T app php bin/console app:calculate-staffing-metrics --range=12
```

## 🔄 Workflow complet

```mermaid
graph LR
    A[Créer/Modifier Planning] --> B[Planning.status: planned/confirmed]
    B --> C[Lancer recalcul métriques]
    C --> D[calculatePlannedDays]
    D --> E[FactStaffingMetrics.plannedDays]
    E --> F[Dashboard affiche taux d'occupation]
```

## 📝 Fichiers modifiés

| Fichier | Modification |
|---------|--------------|
| `src/Service/StaffingMetricsCalculationService.php` | Ajout de `calculatePlannedDays()` |
| `src/Entity/Planning.php` | Aucune (déjà existant) |
| `src/Repository/StaffingMetricsRepository.php` | Aucune (déjà compatible) |
| `templates/staffing/dashboard.html.twig` | Aucune (déjà affiche plannedDays) |

## 🎯 Résultat

✅ Les modifications dans le Planning impactent maintenant le Staffing Dashboard
✅ Les affectations futures sont visibles dans la vue annuelle
✅ Le taux d'occupation inclut les planifications confirmées et prévisionnelles
✅ Les codes couleur aident à identifier les surcharges et sous-utilisations

## 📚 Références

- [Planning & Saisie des temps](./time-planning.md)
- [Dashboard de Staffing](./staffing-dashboard-implementation.md)
- [Entités](./entities.md)
- [Analytics & KPIs](./analytics.md)
