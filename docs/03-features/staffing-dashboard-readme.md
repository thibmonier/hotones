# 📈 Dashboard de Staffing - Guide Rapide

## 🎯 Accès

### Via le Menu
1. Connectez-vous à l'application
2. Dans le menu de gauche, allez dans **Administration**
3. Cliquez sur **Analyses & Rapports**
4. Sélectionnez **📈 Staffing & TACE**

### Via l'URL
- `http://localhost:8080/staffing/dashboard`

### Permissions
- Rôle requis : `ROLE_USER` (tous les utilisateurs connectés)
- Visible uniquement pour les utilisateurs ayant accès au menu Administration (`ROLE_MANAGER`)

## ⚙️ Configuration Requise

### 1. Base de données
Les tables doivent exister :
```bash
docker-compose exec app php bin/console make:migration
docker-compose exec app php bin/console doctrine:migrations:migrate
```

### 2. Périodes d'emploi
Chaque contributeur actif doit avoir au moins une période d'emploi :
```sql
-- Vérifier
SELECT COUNT(*) FROM employment_periods;

-- Créer des périodes de test si nécessaire
INSERT INTO employment_periods (contributor_id, start_date, salary, cjm, tjm, weekly_hours, work_time_percentage)
SELECT id, '2025-01-01', 3500, 400, 500, 35.00, 100.00
FROM contributors 
WHERE active = 1 
AND id NOT IN (SELECT contributor_id FROM employment_periods);
```

### 3. Calcul des métriques
```bash
# Pour les 6 derniers mois
docker-compose exec app php bin/console app:calculate-staffing-metrics --range=6

# Pour un mois spécifique
docker-compose exec app php bin/console app:calculate-staffing-metrics 2025-11

# Pour une année complète
docker-compose exec app php bin/console app:calculate-staffing-metrics 2025
```

## 📊 Métriques Affichées

### Taux de Staffing
**Formule** : `(Temps staffé / Temps disponible) × 100`

**Interprétation** :
- 🟢 **85%+** : Excellent - Bonne utilisation avec marge pour formation
- 🟠 **70-84%** : Correct - Utilisation acceptable
- 🔴 **<70%** : Faible - Sous-utilisation, besoin d'affectations

### TACE (Taux d'Activité Congés Exclus)
**Formule** : `(Jours produits / Jours travaillés hors congés) × 100`

**Interprétation** :
- Proche de 100% : Toutes les heures travaillées sont facturables
- 80-90% : Normal avec du temps pour projets internes
- <70% : Beaucoup de temps non facturable

## 🔍 Filtres Disponibles

- **Contributeur** : Filtrer par contributeur spécifique
- **Granularité** : Mensuel / Trimestriel / Hebdomadaire

## 🎨 Visualisations

### Graphiques
1. **Taux de Staffing** - Courbe d'évolution sur 6 mois
2. **TACE** - Courbe d'évolution sur 6 mois

### Tableaux
1. **Par Profil** - Moyennes par profil métier (dev, lead, chef projet...)
2. **Top 10 Contributeurs** - Classement par taux de staffing

## ⚠️ Dépannage

### Aucune donnée affichée

**1. Vérifier les métriques calculées**
```bash
docker-compose exec app php bin/console dbal:run-sql "SELECT COUNT(*) FROM fact_staffing_metrics"
```

Si 0 :
- ✅ Vérifier que les périodes d'emploi existent
- ✅ Relancer le calcul : `docker-compose exec app php bin/console app:calculate-staffing-metrics --range=6`

**2. Vérifier les timesheets**
```bash
docker-compose exec app php bin/console dbal:run-sql "
SELECT 
    DATE_FORMAT(date, '%Y-%m') as month,
    COUNT(*) as nb_timesheets
FROM timesheets
GROUP BY month
ORDER BY month DESC
LIMIT 6
"
```

Si pas de timesheets récents :
- Les contributeurs doivent saisir leurs temps
- Ou ajuster la période de calcul selon les données existantes

**3. Vérifier les périodes d'emploi**
```bash
docker-compose exec app php bin/console dbal:run-sql "
SELECT 
    c.first_name, 
    c.last_name, 
    ep.start_date, 
    ep.end_date
FROM contributors c
LEFT JOIN employment_periods ep ON c.id = ep.contributor_id
WHERE c.active = 1
"
```

### Taux supérieurs à 100%

C'est normal ! Cela signifie que les contributeurs ont travaillé plus que leur temps théorique disponible :
- Heures supplémentaires
- Week-ends travaillés
- Plus de 8h par jour

## 🔄 Automatisation

Pour mettre à jour automatiquement les métriques chaque jour :

```bash
# Éditer le crontab
crontab -e

# Ajouter cette ligne (calcul à 6h du matin)
0 6 * * * cd /Users/tmonier/Projects/hotones && docker-compose exec -T app php bin/console app:calculate-staffing-metrics --range=1 >> /var/log/staffing-metrics.log 2>&1
```

## 📚 Documentation Complète

- **Implémentation** : `docs/staffing-dashboard-implementation.md`
- **Architecture** : `docs/architecture.md`
- **Analytics** : `docs/analytics.md`
- **Entités** : `docs/entities.md`

## 🆘 Support

En cas de problème :
1. Vérifier les logs : `docker-compose logs app`
2. Exécuter la commande en verbose : `docker-compose exec app php bin/console app:calculate-staffing-metrics 2025-11 -vvv`
3. Consulter la documentation détaillée dans `docs/`
