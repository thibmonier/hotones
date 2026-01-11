# 🗺️ HotOnes - Roadmap Produit Révisée (Janvier 2026)

> **Version optimisée** - Dernière mise à jour : 4 janvier 2026
>
> Cette roadmap révisée **priorise l'excellence technique** avant les développements fonctionnels.
> Elle intègre un nouveau **Lot 0 : Optimisation Technique & Qualité** comme prérequis critique.

## 🎯 Nouvelle Philosophie : "Code Quality First"

### Contexte de la révision
- **État actuel** : PHP 8.5.1 ✅, Symfony 8.x, ~68,000 lignes de code
- **Dette technique identifiée** : Rapport Doctrine Doctor révèle des optimisations critiques
- **Objectif** : Code propre, performant et maintenable avant nouvelles fonctionnalités

### Principes directeurs
1. **Qualité > Quantité** : Réduire les lignes de code, améliorer la maintenabilité
2. **Performance > Features** : Optimiser l'existant avant d'ajouter du nouveau
3. **Standards > Custom** : Utiliser les standards PHP 8.5 (enums, property hooks, etc.)

---

## 📊 Vue d'ensemble révisée

### Statut global
- **Total de lots** : 37 lots (ajout du Lot 0)
- **Terminés** : 5 lots (Lots 2, 3, 7, 11, 12)
- **En cours** : 2 lots (Lot 9 - 35%, Lot 11bis - 40%)
- **Nouveauté** : **Lot 0 - Optimisation Technique (PRIORITÉ #1)**
- **Planifiés** : 30 lots
- **Estimation totale** : ~385-480 jours de développement

### Changements majeurs
- 🆕 **Nouveau Lot 0** : Optimisation Technique & Qualité (15-18 jours)
- ⚡ **Lot 11bis réorienté** : Fusion avec Lot 0 pour éviter duplication
- 📉 **Objectif -20%** : Réduction de 68,000 à ~54,000 lignes de code
- 🚀 **PHP 8.5 First** : Exploitation complète des features PHP 8.5

---

## 🔥 NOUVEAU : Lot 0 - Optimisation Technique & Qualité ⭐⭐⭐

**Estimation :** 15-18 jours | **Statut :** ⏳ Planifié | **PRIORITÉ ABSOLUE**

**Objectif :** Nettoyer, optimiser et moderniser le code existant AVANT tout nouveau développement.

### 0.1 Optimisations Doctrine (4-5 jours)

**Rapport Doctrine Doctor - Fixes critiques :**

#### 0.1.1 Configuration Base de Données (0.5j)
- ✅ Ajouter modes SQL manquants : `NO_ZERO_DATE`, `NO_ZERO_IN_DATE`
- ✅ Charger les tables timezone MySQL (`mysql_tzinfo_to_sql`)
- ✅ Harmoniser collations (utf8mb4_unicode_ci vs utf8mb4_uca1400_ai_ci)

**Fichiers concernés :**
```yaml
# docker-compose.yml ou my.cnf
sql_mode: STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION
```

#### 0.1.2 Correction Requêtes Inefficaces (2-3j)
- 🔴 **CRITIQUE** : Remplacer `setMaxResults()` par `Paginator` pour éviter perte de données
  - **Impact** : Risque de données partiellement chargées (collections)
  - **Fichiers** : Tous les repositories utilisant `setMaxResults()` avec joins

- ⚡ Ajouter `LIMIT` aux requêtes avec `ORDER BY` sans pagination
  - **Impact** : Performance sur grandes tables
  - **Fichiers** : Repositories avec `findBy()` + tri

- ⚡ Remplacer 12 `find()` par `getReference()` (optimisation lazy loading)
  - **Tables concernées** : users, contributors, companies, clients, orders
  - **Gain** : -12 requêtes SQL par opération

**Exemple de refactoring :**
```php
// ❌ AVANT (inefficace)
$user = $em->find(User::class, $userId);
$order->setUser($user);

// ✅ APRÈS (optimisé)
$user = $em->getReference(User::class, $userId);
$order->setUser($user);
```

#### 0.1.3 Traits Blameable (1.5j)
- Ajouter `BlameableTrait` sur **28 entités** manquantes
  - User, Company, BusinessUnit, ContributorSkill, ProjectSubTask, Skill
  - ExpenseReport, Invoice, Vacation, Subscription, SchedulerEntry
  - SaasProvider, OnboardingTask, SaasSubscription, SaasService
  - OnboardingTemplate, ContributorProgress, ContributorSatisfaction
  - CookieConsent, PerformanceReview, ProjectEvent, NpsSurvey
  - LeadCapture, Provider, Notification, FactForecast, Badge, Planning

**Impact :** Audit trail complet (qui a créé/modifié quoi, quand)

### 0.2 Migration PHP 8.5 Features (3-4 jours)

#### 0.2.1 Property Hooks (déjà en cours - finaliser)
- ✅ Déjà commencé sur plusieurs entités
- ⏳ Finaliser migration complète (toutes les entités)
- ⏳ Supprimer getters/setters redondants

**Avant/Après :**
```php
// ❌ AVANT (verbeux)
private string $email;
public function getEmail(): string { return $this->email; }
public function setEmail(string $email): void { $this->email = $email; }

// ✅ APRÈS (concis)
public string $email {
    set(string $value) => strtolower(trim($value));
}
```

**Estimation réduction lignes** : -15 à -20% sur les entités

#### 0.2.2 Native Enums (1-1.5j)
**6 champs identifiés pour conversion** :
- `Contributor::$gender` (1 valeur distincte / 59 rows)
- `Project::$projectType` (2 valeurs / 75 rows)
- `Order::$contractType` (2 valeurs / 75 rows)
- `OrderLine::$type` (1 valeur / 48 rows)
- `ProjectEvent::$eventType` (1 valeur / 75 rows)
- `Planning::$status` (2 valeurs / 94 rows)

**Exemple :**
```php
// ❌ AVANT
class Project {
    #[ORM\Column(type: 'string')]
    private string $projectType; // 'forfait' ou 'regie'
}

// ✅ APRÈS
enum ProjectType: string {
    case FORFAIT = 'forfait';
    case REGIE = 'regie';
}

class Project {
    #[ORM\Column(enumType: ProjectType::class)]
    public ProjectType $projectType;
}
```

**Avantages :**
- Type safety (impossible d'avoir une valeur invalide)
- Autocomplete IDE
- Refactoring sûr
- Réduction code de validation

#### 0.2.3 Typed Properties & Strict Types (0.5j)
- Audit complet : tous les fichiers doivent avoir `declare(strict_types=1);`
- Typage strict de toutes les propriétés
- Suppression des `@var` phpdoc redondants avec typage natif

#### 0.2.4 Readonly Properties (1j)
- Identifier et marquer les propriétés immuables comme `readonly`
- Exemples : IDs, timestamps de création, entités de référence

```php
// ✅ APRÈS
class Order {
    public readonly int $id;
    public readonly \DateTimeImmutable $createdAt;
    public readonly Company $company; // Immutable après création
}
```

### 0.3 Nettoyage & Réduction Code (4-5 jours)

#### 0.3.1 Suppression Code Mort (1.5j)
- Analyse statique avec PHPStan niveau 9 (monter de niveau 3 à 9)
- Recherche méthodes/classes non utilisées
- Suppression code commenté
- Suppression fichiers/templates obsolètes

**Outils :**
```bash
composer require --dev phpstan/phpstan-deprecation-rules
composer require --dev phpstan/phpstan-strict-rules
./vendor/bin/phpstan analyse --level 9
```

#### 0.3.2 Factorisation Code Dupliqué (2j)
- Recherche duplications avec PHP Copy/Paste Detector
- Extraction vers traits/services partagés
- Création d'abstractions communes (ex: `AbstractCompanyAwareRepository`)

```bash
composer require --dev sebastian/phpcpd
./vendor/bin/phpcpd src/
```

#### 0.3.3 Simplification Logique Business (1-1.5j)
- Refactoring méthodes complexes (Cyclomatic Complexity > 10)
- Extraction de méthodes privées
- Application du principe SRP (Single Responsibility)

**Objectif :** Réduire la complexité cyclomatique moyenne

### 0.4 Tests & Qualité (2-3 jours)

#### 0.4.1 Augmentation Couverture Tests (1.5-2j)
- **Objectif** : Passer de 15% à 50%
- **Focus** : Services critiques modifiés dans Lot 0
- Tests unitaires sur nouveaux traits
- Tests d'intégration sur repositories optimisés

#### 0.4.2 Mutation Testing (0.5-1j)
- Configuration Infection avec baseline
- Objectif MSI (Mutation Score Indicator) > 70%
- Identifier tests faibles

```bash
./vendor/bin/infection --threads=4 --min-msi=70
```

### 0.5 Performance & Profiling (2-3 jours)

#### 0.5.1 Blackfire Profiling (1j)
- Profile des 10 endpoints les plus utilisés
- Identification des requêtes N+1 résiduelles
- Optimisation des queries lentes (> 100ms)

#### 0.5.2 Indexation Base de Données (0.5j)
- Analyse `EXPLAIN` sur requêtes critiques
- Ajout d'index composites manquants
- Vérification Foreign Keys indexées

#### 0.5.3 Cache Strategy (0.5-1j)
- Mise en place Redis pour cache applicatif
- Cache Doctrine queries (result cache + query cache)
- Cache HTTP avec annotations `@Cache`

### 0.6 Documentation & Standards (1j)

#### 0.6.1 Documentation Architecture (0.5j)
- Diagrammes Mermaid mis à jour (entités, flows)
- Documentation des patterns utilisés (Repository, Service Layer)
- ADR (Architecture Decision Records) pour choix techniques

#### 0.6.2 Coding Standards (0.5j)
- Configuration PHP CS Fixer avec règles PHP 8.5
- Configuration PHPStan niveau 9
- Pre-commit hooks pour qualité

---

## 📊 Gains Attendus du Lot 0

### Métriques de Qualité

| Métrique | Avant | Objectif Après | Gain |
|----------|-------|----------------|------|
| **Lignes de code** | 68,000 | 54,000 | **-20%** |
| **Couverture tests** | 15% | 50% | **+233%** |
| **PHPStan niveau** | 3 | 9 | **+200%** |
| **Mutation Score** | N/A | 70% | **Nouveau** |
| **Complexité cyclomatique** | ~8 | ~5 | **-37%** |
| **Requêtes SQL (avg)** | ~25/page | ~15/page | **-40%** |
| **Temps réponse (avg)** | ~250ms | ~150ms | **-40%** |

### ROI Estimé

- **Réduction bugs production** : -50% (grâce aux tests + types stricts)
- **Vélocité développement** : +30% (code plus simple et lisible)
- **Maintenabilité** : +60% (moins de code, mieux organisé)
- **Performance** : +40% (optimisations Doctrine + cache)
- **Onboarding nouveau dev** : -40% de temps (code plus clair)

---

## 🗓️ Planning Révisé 2026

### Q1 2026 (Janvier - Mars) - EXCELLENCE TECHNIQUE

#### Janvier 2026 (3 semaines)
**Semaines 1-3 : LOT 0 - Optimisation Technique** ⭐⭐⭐
- Semaine 1 : Optimisations Doctrine (0.1)
- Semaine 2 : Migration PHP 8.5 Features (0.2) + Nettoyage (0.3)
- Semaine 3 : Tests & Performance (0.4, 0.5) + Documentation (0.6)

**Livrable :** Code base propre, optimisé et testé à 50%

#### Février - Mars 2026 (6-8 semaines)
**LOT 6 : Conformité RGPD** 🔴 (URGENT - obligation légale)
- Semaines 4-10 : RGPD complet (35-37 jours)

**Pourquoi après Lot 0 ?**
- Code base sain = implémentation RGPD plus rapide
- Tests solides = garantie conformité
- Performance optimisée = pas de dégradation avec RGPD

### Q2 2026 (Avril - Juin) - FONDATIONS MÉTIER

#### Avril 2026
- **Lot 9** : Finaliser Cohérence UX/UI (7-8 jours restants)
- **Lot 1** : CRUD Entités Principales (8-10 jours)

#### Mai - Juin 2026
- **Lot 13** : Liste Projets KPIs (3-4 jours)
- **Lot 14** : Détail Projet Graphiques (4-5 jours)
- **Lot 5** : Module Facturation (10-12 jours)

### Q3 2026 (Juillet - Septembre) - PROFESSIONNALISATION

- **Lot 15** : Rapports & Exports (6-7 jours)
- **Lot 17** : Signature Électronique (10-11 jours)
- **Lot 18** : API REST (8-10 jours)
- **Lot 19** : Analytics Prédictifs (12-15 jours)

### Q4 2026 (Octobre - Décembre) - TRANSFORMATION

- **Lot 20** : Dashboard RH (8-10 jours)
- **Lot 23** : Transformation SAAS Multi-Tenant ⭐ (45-55 jours)
- **Lot 24** : Business Units Post-SAAS (6-8 jours)

### 2027 - EXPANSION & CONFORMITÉ

- **Q1 2027** : Lot 16 - Facturation Électronique (obligation sept 2027)
- **Q2-Q4 2027** : Lots 21-35 (Intégrations, Mobile, Optimisations)

---

## 🎯 Prochaines Étapes Immédiates

### Semaine 1 (6-10 janvier 2026)
1. ✅ Configurer modes SQL stricts (0.1.1)
2. ✅ Identifier toutes les requêtes avec `setMaxResults()` + collections
3. ✅ Créer `BlameableTrait` et `TimestampableTrait`
4. ✅ Lister tous les candidats pour enums
5. ✅ Setup PHPStan niveau 9 + baseline

### Semaine 2 (13-17 janvier 2026)
1. Remplacer `setMaxResults()` par `Paginator` (0.1.2)
2. Convertir 6 champs en native enums (0.2.2)
3. Finaliser property hooks sur toutes les entités (0.2.1)
4. Ajouter `BlameableTrait` sur 28 entités (0.1.3)
5. Lancer analyse PHPCPD pour duplications

### Semaine 3 (20-24 janvier 2026)
1. Refactoring code dupliqué (0.3.2)
2. Supprimer code mort identifié (0.3.1)
3. Tests critiques (couverture 50%) (0.4.1)
4. Profiling Blackfire + optimisations (0.5.1)
5. Documentation architecture (0.6)

### Validation Lot 0 (27 janvier 2026)
**Critères de réussite :**
- ✅ Lignes de code < 55,000 (-19%)
- ✅ Couverture tests > 48%
- ✅ PHPStan niveau 9 : 0 erreur
- ✅ Mutation Score > 68%
- ✅ Temps réponse moyen < 160ms
- ✅ 0 requête N+1 sur parcours critiques

---

## 📋 Checklist Technique Lot 0

### Configuration & Infrastructure
- [ ] Modes SQL stricts activés (NO_ZERO_DATE, NO_ZERO_IN_DATE)
- [ ] Timezone tables MySQL chargées
- [ ] Collations harmonisées (utf8mb4_unicode_ci)
- [ ] Redis configuré (cache applicatif)
- [ ] PHPStan niveau 9 configuré
- [ ] Infection (mutation testing) configuré

### Optimisations Doctrine
- [ ] `Paginator` remplace tous les `setMaxResults()` avec collections
- [ ] `LIMIT` ajouté sur tous les `ORDER BY` sans pagination
- [ ] `getReference()` remplace `find()` pour associations (12 occurrences)
- [ ] Index composites ajoutés sur colonnes critiques
- [ ] Cache Doctrine activé (result + query cache)

### Migration PHP 8.5
- [ ] Property hooks sur toutes les entités (100%)
- [ ] 6 enums natifs créés (ProjectType, ContractType, etc.)
- [ ] Typed properties strict partout
- [ ] `readonly` sur propriétés immuables
- [ ] `declare(strict_types=1)` sur tous les fichiers

### Qualité & Tests
- [ ] `BlameableTrait` ajouté sur 28 entités
- [ ] Couverture tests > 50%
- [ ] Mutation Score > 70%
- [ ] Complexité cyclomatique moyenne < 6
- [ ] 0 code mort (détecté par PHPStan niveau 9)

### Performance
- [ ] Profiling Blackfire complété
- [ ] Temps réponse moyen < 150ms
- [ ] Requêtes SQL moyennes < 15/page
- [ ] 0 requête N+1 sur dashboards

### Documentation
- [ ] Diagrammes architecture mis à jour
- [ ] ADR documentés pour décisions techniques
- [ ] README technique à jour
- [ ] Changelog Lot 0 complet

---

## 🚀 Bénéfices à Long Terme

### Pour les Développeurs
- **Code plus lisible** : -20% de lignes = -20% de complexité mentale
- **Refactoring sûr** : Types stricts + tests = confiance
- **Onboarding rapide** : Code moderne et documenté
- **Moins de bugs** : Enums + property hooks = erreurs impossibles

### Pour le Business
- **Vélocité accrue** : +30% de rapidité sur nouveaux lots
- **Moins d'incidents** : -50% de bugs en production
- **Meilleure performance** : -40% temps de réponse = meilleure UX
- **Scalabilité** : Base saine pour transformation SAAS (Lot 23)

### Pour la Maintenance
- **Dette technique réduite** : Proactive vs réactive
- **Coûts optimisés** : Moins de temps de correction
- **Evolutivité** : Nouvelles features plus simples à ajouter
- **Conformité facilitée** : RGPD sur code propre = plus simple

---

## Lot 36 : Refonte Pages Publiques ⭐

**Estimation :** 5-7 jours | **Statut :** ✅ Terminé (11 janvier 2026) | **Priorité :** 🟡 Moyenne

### Objectif
Moderniser les pages publiques marketing avec design light, personnage Unit404 et nouveau pricing pour améliorer l'image de marque et la conversion.

### Contexte
Les pages publiques actuelles présentaient plusieurs problèmes :
- Thème dark by default peu adapté au grand public
- Absence de toggle dark/light
- Images placeholder (picsum.photos)
- Langage trop technique (analytics BI, schéma en étoile, CJM/TJM)
- Pricing obsolète (12€/19€ par utilisateur)
- RGPD peu mis en avant

### Modules Réalisés

#### 36.1 Infrastructure Thème (1.5j) - ✅ Terminé
- ✅ CSS Variables light/dark avec auto-détection `prefers-color-scheme`
- ✅ Toggle dark/light persistant (localStorage) dans navbar publique
- ✅ Extraction CSS inline → fichiers externes (`public-theme.css`, `public-pages.css`)
- ✅ Script de thème chargé en premier pour éviter FOUC

**Fichiers créés :**
- `public/assets/css/public-theme.css` (150 lignes) - Variables CSS
- `public/assets/css/public-pages.css` (500 lignes) - Styles extraits
- `public/assets/js/public-theme-toggle.js` (80 lignes) - Toggle + localStorage

**Impact :** Thème light par défaut adapté au B2B, dark mode optionnel

#### 36.2 Contenu & Navigation (1j) - ✅ Terminé
- ✅ Bandeau beta "Projet en développement" avec lien "En savoir plus"
- ✅ Lien navbar "Intégrateurs" vers API docs (`api_docs_ui`)
- ✅ Intégration Unit404 : 14 variations sur toutes les pages
  - Homepage : 2 images (hero + features)
  - Features : 4 images (Time Tracking, Project Management, Analytics, Planning)
  - Pricing : 3 images (Starter, Business, Enterprise)

**Fichiers créés :**
- `templates/public/_beta-banner.html.twig` (10 lignes)

**Configuration :**
- `config/packages/liip_imagine.yaml` : 3 nouveaux filtres (unit404_hero, unit404_section, unit404_pricing)

**Impact :** Identité visuelle forte avec mascotte Unit404, transparence sur le statut beta

#### 36.3 Rewriting Marketing (1.5j) - ✅ Terminé
**Homepage :**
- Hero : "Gérez votre agence web avec **sérénité**" (au lieu de "rentabilité")
- Simplification : "L'assistant intelligent" au lieu de "analytics de niveau BI"
- Suppression jargon : "Schéma en étoile", "Data Warehouse", "TACE", "CJM/TJM"
- Focus bénéfices clients : "Tableaux de bord clairs", "Anticipez les problèmes"

**Features Page :**
- Analytics : "Analyses prédictives avancées" au lieu de "Schéma en étoile"
- Planning : "Planning équipe optimisé" au lieu de "Dashboard staffing complet"
- Langage accessible : "Anticipez vos revenus futurs" au lieu de "Forecasting"

**Pricing Page :**
- **Nouveau modèle par lots** (vs par utilisateur) :
  - **Starter** : 299€/mois pour 5-15 utilisateurs
  - **Business** : 699€/mois pour 16-50 utilisateurs (ex-Professional)
  - **Enterprise** : 1299€/mois pour 51-150 utilisateurs (vs "Sur mesure")
- Mise en avant IA : "IA complète (Unit404)" comme différenciateur Business
- Simplification features : "Connexion entreprise sécurisée" au lieu de "SSO/SAML"
- Images Unit404 sur chaque plan

**Impact :** Langage 100% marketing, pricing adapté marché français, différenciation claire

#### 36.4 RGPD & Sécurité (0.5j) - ✅ Terminé
**Footer enrichi :**
- Nouvelle colonne "Sécurité & Conformité" :
  - 🇫🇷 Hébergement France
  - Conformité RGPD
  - Chiffrement SSL/TLS
  - ISO 27001
- Copyright mis à jour : "Hébergé en France 🇫🇷 • RGPD Compliant"

**Page Legal enrichie :**
- **Section RGPD** (162 lignes) :
  - Hébergement des données (localisation, provider, garanties)
  - Zones géographiques (Roubaix, Strasbourg, Paris, Amsterdam)
  - Sécurité & Protection (TLS 1.3, AES-256, 2FA, ISO 27001)
  - Vos droits RGPD (8 droits détaillés avec icônes)
  - Contact DPO (dpo@hotones.io, adresse postale)
- **Section Infrastructure Cloud** (98 lignes) :
  - Datacenters certifiés (ISO 27001, ISO 14001, Tier III+)
  - Performances (SLA 99.9%, latence <50ms, CDN européen, API <200ms)
  - Monitoring 24/7

**Impact :** Conformité RGPD mise en avant, rassure prospects B2B français

#### 36.5 Assets Authentiques (0j) - ⏸️ Reporté
- ⏸️ Screenshots app (6 images) - Reporté à plus tard
- ⏸️ Unit404 + UI floué background - Non prioritaire

**Raison :** Images Unit404 suffisantes pour cette phase, screenshots réels pourront être ajoutés ultérieurement

#### 36.6 Tests & Validation (0j) - ⏳ À faire
- ⏳ Tests fonctionnels (toggle, responsive, images)
- ⏳ Tests performance (Lighthouse > 90)

### Dépendances
- ✅ Lot 7 : Pages d'Erreur (terminé)
- 🔄 Lot 9 : UX/UI Globale (35%)

### Livrables
- ✅ Pages publiques light by default
- ✅ Toggle dark/light fonctionnel
- ✅ Unit404 sur toutes les pages
- ✅ Nouveau pricing 299€/699€/1299€
- ✅ RGPD mis en avant
- ✅ Textes 100% marketing (0 jargon technique)

### Impact Commercial
**Avant :**
- Thème dark peu engageant pour B2B
- Pricing flou (par utilisateur)
- Langage technique rebutant
- RGPD peu visible

**Après :**
- Thème light professionnel avec option dark
- Pricing clair par tranches
- Langage simple et bénéfices clients
- RGPD rassurant et visible

**ROI attendu :**
- +30% taux de conversion landing pages
- -40% taux de rebond homepage
- +50% temps passé sur site
- Meilleure perception de marque (mascotte Unit404)

### Risques & Mitigations
| Risque | Impact | Mitigation | Résultat |
|--------|--------|------------|----------|
| FOUC (Flash of Unstyled Content) | Moyen | Script inline en head | ✅ Résolu |
| Images Unit404 ne chargent pas | Faible | Fallback CSS placeholder | ✅ Liip Imagine OK |
| Textes encore trop techniques | Moyen | Revue par non-tech | ✅ Simplifié |
| Route API manquante | Faible | Vérification routes | ✅ Corrigé (api_docs_ui) |

### Fichiers Modifiés
**Templates (5 fichiers) :**
- `templates/public/base.html.twig` - Navbar, footer, copyright
- `templates/public/homepage.html.twig` - Hero, features, images
- `templates/public/features.html.twig` - Images Unit404
- `templates/public/pricing.html.twig` - Nouveau pricing 299€/699€/1299€
- `templates/public/legal.html.twig` - Sections RGPD + Infrastructure

**Configuration (1 fichier) :**
- `config/packages/liip_imagine.yaml` - 3 filtres Unit404

**Assets (3 fichiers) :**
- `public/assets/css/public-theme.css` (nouveau)
- `public/assets/css/public-pages.css` (nouveau)
- `public/assets/js/public-theme-toggle.js` (nouveau)

### Métriques de Succès
- ✅ 14 images Unit404 intégrées
- ✅ 1541 lignes CSS inline → 650 lignes externes
- ✅ Toggle dark/light fonctionnel
- ✅ 0 jargon technique restant
- ✅ Footer RGPD enrichi
- ✅ 260 lignes ajoutées à page legal (RGPD + Infrastructure)

### Prochaines Étapes
1. Phase 8 : Tests & Validation
   - Tests fonctionnels (toggle, responsive)
   - Tests performance (Lighthouse)
   - Validation multi-navigateurs
2. Phase optionnelle : Assets authentiques
   - Screenshots app réels
   - Unit404 + UI floué background

---

## 📊 Comparaison Ancienne vs Nouvelle Roadmap

| Aspect | Ancienne Roadmap | Nouvelle Roadmap | Amélioration |
|--------|------------------|------------------|--------------|
| **Priorité #1** | Lot 1 (CRUD) | **Lot 0 (Qualité)** | Technique first |
| **Dette technique** | Lot 11bis (partiel) | **Lot 0 (complet)** | +100% couverture |
| **PHP 8.5** | Lot 35 (fin 2026) | **Lot 0 (janvier 2026)** | +11 mois d'avance |
| **Tests** | 15% → 60% (progressif) | **15% → 50% (immédiat)** | Validation rapide |
| **Optimisation Doctrine** | Non planifié | **Lot 0 (critique)** | Gain +40% perf |
| **Réduction code** | Non planifié | **-14,000 lignes** | +20% maintenabilité |

---

## ⚠️ Risques & Mitigation

### Risques Identifiés

1. **Régression fonctionnelle** (Probabilité: Moyenne)
   - **Mitigation** : Tests intensifs (50% couverture) + mutation testing
   - **Plan B** : Baseline PHPStan pour rollback partiel

2. **Dépassement délai** (Probabilité: Faible)
   - **Mitigation** : Découpage en sprints hebdomadaires
   - **Plan B** : Prioriser 0.1 et 0.2 si contrainte temps

3. **Breaking changes PHP 8.5** (Probabilité: Très faible)
   - **Mitigation** : PHP 8.5 déjà stable et testé
   - **Plan B** : Rollback vers PHP 8.4 si critique

4. **Résistance équipe** (Probabilité: Faible)
   - **Mitigation** : Démonstration gains concrets (benchmarks)
   - **Plan B** : Formation PHP 8.5 moderne

---

## 📚 Ressources & Documentation

### Documentation Technique
- [PHP 8.5 Release Notes](https://www.php.net/releases/8.5/en.php)
- [Property Hooks RFC](https://wiki.php.net/rfc/property-hooks)
- [Doctrine Best Practices](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/best-practices.html)
- [Symfony Performance](https://symfony.com/doc/current/performance.html)

### Outils & Libraries
- PHPStan (niveau 9) : Analyse statique stricte
- PHP CS Fixer : Standards PHP 8.5
- Infection : Mutation testing
- PHPCPD : Détection code dupliqué
- Blackfire : Profiling performance

### Documents Projet
- `docs/11-reports/doctrine-doctor-report.md` - Rapport d'audit Doctrine
- `docs/11-reports/technical-audit-lot11bis-2025-12-28.md` - Audit technique
- `docs/11-reports/technical-debt-hotspots-2025-12-28.md` - Hotspots dette technique
- `WARP.md` - Index documentation

---

## ✅ Conclusion

**Le Lot 0 n'est pas une option, c'est un investissement stratégique.**

En priorisant la qualité technique AVANT les nouvelles fonctionnalités, nous créons une base solide pour :
- Accélérer le développement futur (+30% vélocité)
- Réduire drastiquement les bugs (-50%)
- Améliorer l'expérience utilisateur (+40% performance)
- Faciliter la transformation SAAS (Lot 23)
- Garantir la conformité RGPD (Lot 6)

**Temps investi** : 15-18 jours (3 semaines)
**ROI sur 12 mois** : Économie estimée de 60-80 jours de correction/optimisation

---

**Prochaine action recommandée** : 🚀 Démarrer Lot 0 - Sprint 1 (Semaine du 6 janvier 2026)

**Dernière mise à jour** : 4 janvier 2026
**Version** : 2.0 (Révision majeure - Code Quality First)
**Statut** : ✅ Validé pour exécution
