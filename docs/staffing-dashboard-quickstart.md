# 🚀 Guide de Mise en Route - Dashboard de Staffing

## Problème résolu

Le dashboard de staffing n'affichait pas de données car :
1. ❌ Les tables de base de données n'existaient pas encore (migrations non créées)
2. ❌ Les métriques n'avaient pas été calculées
3. ✅ **Bug corrigé** : Le service calculait les métriques sur un seul jour au lieu du mois complet

## ✅ Corrections apportées

### 1. Correction du service de calcul
- Ajout de la méthode `calculatePeriodEnd()` pour calculer la fin de période selon la granularité
- Le calcul se fait maintenant sur tout le mois (premier au dernier jour) au lieu d'un seul jour

### 2. Amélioration de l'UI
- Message d'alerte informatif si aucune donnée n'est disponible
- Instructions pas à pas pour générer les données
- Interface cohérente avec le dashboard analytics

## 📋 Étapes de Mise en Route

### Étape 1 : Créer les tables de base de données

```bash
# Générer les migrations
php bin/console make:migration

# Vérifier la migration générée dans migrations/
# Elle devrait créer les tables :
# - dim_profile
# - fact_staffing_metrics

# Appliquer les migrations
php bin/console doctrine:migrations:migrate
```

### Étape 2 : Calculer les métriques

```bash
# Calculer pour les 12 derniers mois (recommandé)
php bin/console app:calculate-staffing-metrics --range=12

# Ou pour une année spécifique
php bin/console app:calculate-staffing-metrics 2024

# Ou pour un mois spécifique
php bin/console app:calculate-staffing-metrics 2024-11
```

**Note** : Le calcul peut prendre quelques minutes selon le volume de données (contributeurs × périodes × timesheets).

### Étape 3 : Vérifier les données

Accédez au dashboard : `http://localhost/staffing/dashboard`

Vous devriez voir :
- ✅ Les graphiques d'évolution du taux de staffing et TACE
- ✅ Les tableaux par profil et par contributeur
- ✅ Les filtres fonctionnels

## 🔍 Vérification de la base de données

Pour vérifier que les données ont bien été calculées :

```sql
-- Vérifier les métriques
SELECT COUNT(*) FROM fact_staffing_metrics;

-- Voir quelques exemples de données
SELECT 
    dt.year_month,
    c.first_name,
    c.last_name,
    fsm.staffing_rate,
    fsm.tace
FROM fact_staffing_metrics fsm
JOIN dim_time dt ON fsm.dim_time_id = dt.id
JOIN contributors c ON fsm.contributor_id = c.id
ORDER BY dt.date DESC
LIMIT 10;
```

## 📊 Que faire si les graphiques sont vides ?

### Vérification 1 : Les contributeurs ont-ils des périodes d'emploi ?

```sql
SELECT c.first_name, c.last_name, COUNT(ep.id) as nb_periods
FROM contributors c
LEFT JOIN employment_periods ep ON c.id = ep.contributor_id
WHERE c.active = 1
GROUP BY c.id;
```

**Solution** : Assurez-vous que chaque contributeur actif a au moins une période d'emploi avec des dates valides.

### Vérification 2 : Y a-t-il des timesheets ?

```sql
SELECT 
    DATE_FORMAT(t.date, '%Y-%m') as month,
    COUNT(*) as nb_timesheets,
    SUM(t.hours) as total_hours
FROM timesheets t
WHERE t.date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
GROUP BY DATE_FORMAT(t.date, '%Y-%m')
ORDER BY month DESC;
```

**Solution** : Il faut des timesheets pour calculer les jours staffés. Si aucun timesheet n'existe, le taux sera à 0%.

### Vérification 3 : La commande s'est-elle exécutée sans erreur ?

Relancez la commande en mode verbeux :

```bash
php bin/console app:calculate-staffing-metrics --range=12 -v
```

Regardez les erreurs potentielles :
- Contributeurs sans période d'emploi (normal, ils sont skippés)
- Erreurs de base de données
- Problèmes de calcul de dates

## 🎯 Données de test

Si vous n'avez pas encore de données réelles, vous pouvez créer des données de test :

```sql
-- Exemple : Ajouter une période d'emploi pour un contributeur
INSERT INTO employment_periods (contributor_id, start_date, cjm, tjm, weekly_hours, work_time_percentage)
VALUES (1, '2024-01-01', 400, 500, 35.00, 100.00);

-- Exemple : Ajouter des timesheets
INSERT INTO timesheets (contributor_id, project_id, date, hours)
VALUES 
    (1, 1, '2024-11-01', 8),
    (1, 1, '2024-11-04', 7),
    (1, 1, '2024-11-05', 8);
```

Puis recalculez :

```bash
php bin/console app:calculate-staffing-metrics 2024-11 --force-recalculate
```

## 🔄 Automatisation (Optionnel)

Pour mettre à jour les métriques automatiquement chaque jour :

```bash
# Ajouter au crontab
crontab -e

# Ajouter cette ligne (calcul tous les jours à 6h du matin)
0 6 * * * cd /Users/tmonier/Projects/hotones && php bin/console app:calculate-staffing-metrics --range=1 >> /var/log/staffing-metrics.log 2>&1
```

## 📈 Interprétation des résultats

### Taux de Staffing

- **85%+** : ✅ Excellent - Bonne utilisation avec marge pour formation
- **70-84%** : ⚠️ Correct - Utilisation acceptable
- **<70%** : ❌ Faible - Sous-utilisation, besoin d'affectations

### TACE (Taux d'Activité Congés Exclus)

Mesure la productivité effective :
- Proche de 100% : Toutes les heures travaillées sont facturables
- Entre 80-90% : Normal avec du temps pour projets internes
- <70% : Beaucoup de temps non facturable

## ❓ FAQ

### Q : Pourquoi le TACE est-il différent du taux de staffing ?

Le taux de staffing inclut les congés dans le temps disponible, tandis que le TACE les exclut.

**Exemple** :
- 20 jours ouvrés dans le mois
- 2 jours de congés
- 15 jours staffés

```
Taux de staffing = 15 / 20 = 75%
TACE = 15 / (20 - 2) = 15 / 18 = 83.33%
```

### Q : La commande prend beaucoup de temps

C'est normal si vous avez beaucoup de contributeurs et de données historiques. Le calcul traite :
- Nombre de mois × Nombre de contributeurs × Calculs complexes

Pour accélérer :
- Calculez mois par mois : `php bin/console app:calculate-staffing-metrics 2024-11`
- Puis : `php bin/console app:calculate-staffing-metrics 2024-10`, etc.

### Q : Les données ne correspondent pas à ce que j'attends

Vérifiez :
1. Les périodes d'emploi sont-elles correctes ?
2. Les congés sont-ils marqués comme "approved" ?
3. Les timesheets sont-ils sur la bonne période ?

## 📚 Ressources

- Documentation complète : `docs/staffing-dashboard-implementation.md`
- Architecture Analytics : `docs/analytics.md`
- Entités : `docs/entities.md`
