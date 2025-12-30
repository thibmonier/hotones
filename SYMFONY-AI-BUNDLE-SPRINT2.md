# Symfony AI Bundle - Sprint 2

**Date:** 2025-12-30
**Status:** ✅ Complété
**Durée:** ~1h

## 📋 Résumé

Sprint 2 de l'adoption du Symfony AI Bundle : création de 3 tools métier pour rendre l'agent `quote_generator` autonome et capable d'accéder aux données historiques pour des devis contextualisés.

## 🎯 Objectifs atteints

- ✅ Création de 3 tools métier avec attribut `#[AsTool]`
- ✅ Activation du tool calling dans l'agent `quote_generator`
- ✅ Configuration de l'agent pour utiliser les tools automatiquement
- ✅ Tests de régression (458 tests passent)

## 🛠️ Tools créés

### 1. ClientHistoryTool

**Attribut:** `#[AsTool('get_client_history', "Récupère l'historique de projets d'un client par son nom")]`

**Fonctionnalité:**
- Recherche un client par nom (fuzzy search)
- Retourne le nombre total de projets
- Liste les 5 projets les plus récents (nom, status, type)
- Indique le niveau de service du client

**Données retournées:**
```php
[
    'client_name' => string,
    'client_found' => bool,
    'total_projects' => int,
    'recent_projects' => [
        ['name' => ..., 'status' => ..., 'type' => ...],
        // ...
    ],
    'service_level' => string|null,
]
```

### 2. ProjectStatsTool

**Attribut:** `#[AsTool('get_project_stats', "Récupère les statistiques d'un type de projet")]`

**Fonctionnalité:**
- Calcule les statistiques pour un type de projet (forfait, régie, maintenance)
- Durée moyenne en jours
- Budget moyen
- Statuts les plus fréquents

**Données retournées:**
```php
[
    'project_type' => string,
    'total_projects' => int,
    'stats' => [
        'avg_duration_days' => float,
        'avg_budget' => float,
        'common_statuses' => ['status' => count, ...],
    ],
]
```

### 3. CompanyInfoTool

**Attribut:** `#[AsTool('get_company_info', "Récupère les informations et coefficients de l'entreprise")]`

**Fonctionnalité:**
- Récupère les coefficients de l'entreprise (structure, charges)
- Nombre de jours de congés et RTT
- Informations pour calculs de coûts précis

**Données retournées:**
```php
[
    'structure_cost_coefficient' => float,        // ~1.35
    'employer_charges_coefficient' => float,      // ~1.45
    'global_charge_coefficient' => float,         // produit des 2
    'annual_paid_leave_days' => int,              // 25
    'annual_rtt_days' => int,                     // 10
    'total_leave_days' => int,                    // 35
]
```

## 🔧 Configuration modifiée

### `config/packages/ai.yaml`

Activation du tool calling dans l'agent `quote_generator` :

```yaml
quote_generator:
    platform: 'ai.platform.openai'
    model: 'gpt-4o-mini'
    prompt:
        text: |
            Tu es un expert en estimation de projets web.
            Utilise les outils disponibles pour récupérer:
            - L'historique du client (get_client_history)
            - Les statistiques de projets similaires (get_project_stats)
            - Les informations de l'entreprise (get_company_info)

            Génère une liste de lignes de devis (tâches) estimées.
            Format JSON : liste d'objets avec 'title', 'description', 'days'.
        include_tools: true  # ✅ Active le tool calling
```

## 📊 Enregistrement automatique

Symfony AI Bundle détecte automatiquement les tools grâce à l'attribut `#[AsTool]` :

```bash
$ php bin/console debug:container --tag=ai.tool

Service ID                      name                 description
App\AI\Tool\ClientHistoryTool   get_client_history   Récupère l'historique...
App\AI\Tool\CompanyInfoTool     get_company_info     Récupère les informations...
App\AI\Tool\ProjectStatsTool    get_project_stats    Récupère les statistiques...
```

## 🎨 Architecture

```
Agent quote_generator (GPT-4o-mini)
    ↓ (appel automatique des tools)
    ├─→ ClientHistoryTool → ClientRepository, ProjectRepository
    ├─→ ProjectStatsTool → ProjectRepository
    └─→ CompanyInfoTool → CompanySettingsRepository
```

**Flux d'exécution:**

1. User appelle `AiAssistantService::generateQuoteLines("Site e-commerce pour ClientX")`
2. L'agent `quote_generator` reçoit le prompt
3. L'agent **décide automatiquement** d'appeler :
   - `get_client_history("ClientX")` → Récupère l'historique
   - `get_project_stats("forfait")` → Récupère les statistiques
   - `get_company_info()` → Récupère les coefficients
4. L'agent utilise ces données pour générer un devis précis et contextualisé
5. Retourne le JSON avec les lignes de devis

## 📈 Bénéfices

| Avant Sprint 2 | Après Sprint 2 | Amélioration |
|----------------|----------------|--------------|
| Devis génériques | Devis contextualisés | **+Précision** |
| Pas d'accès aux données | Accès historique client | **+Pertinence** |
| Estimations arbitraires | Basées sur statistiques | **+Réalisme** |
| Aucune connaissance client | Historique automatique | **+Personnalisation** |

## 🧪 Tests

```bash
composer test
```

**Résultat:** ✅ 458 tests, 1410 assertions - Tous passent

## 📚 Fichiers créés/modifiés

**Nouveaux fichiers:**
- ✅ `src/AI/Tool/ClientHistoryTool.php`
- ✅ `src/AI/Tool/ProjectStatsTool.php`
- ✅ `src/AI/Tool/CompanyInfoTool.php`

**Fichiers modifiés:**
- ✅ `config/packages/ai.yaml` - Activation tool calling dans quote_generator

## 💡 Exemple d'utilisation

```php
// Le service AiAssistantService n'a PAS changé
$service = $container->get(AiAssistantService::class);

// Mais maintenant, l'agent va automatiquement:
// 1. Appeler get_client_history("ACME Corp")
// 2. Appeler get_project_stats("forfait")
// 3. Appeler get_company_info()
// 4. Générer un devis basé sur ces données
$lines = $service->generateQuoteLines(
    "Site e-commerce pour ACME Corp avec 50 produits"
);

// Résultat: devis contextualisé basé sur:
// - L'historique des projets ACME Corp
// - Les statistiques de projets similaires
// - Les coefficients de l'entreprise
```

## 🚀 Prochaines étapes (Sprint 3)

1. **Setup RAG avec documentation** (4-5 jours)
   - Installation ChromaDB ou Memory store
   - Indexation de la documentation technique (docs/, CLAUDE.md)
   - Création `DocumentationSearchTool`
   - Tests chatbot support interne avec accès docs

2. **Migration PlanningAIAssistant**
   - Intégration tools dans planning
   - Amélioration recommandations avec contexte

## 📝 Notes techniques

1. **Autowiring automatique:** Les tools sont des services Symfony normaux, auto-injectés
2. **Type safety:** Retours de tools typés avec PHPDoc pour meilleur support IDE
3. **Error handling:** Chaque tool gère ses erreurs et retourne des messages clairs
4. **Performance:** Queries optimisées, pas de N+1, utilisation indexes DB

## 🔗 Documentation

- [Symfony AI Tools](https://symfony.com/doc/current/ai/bundles/ai-bundle.html#tools)
- [Sprint 1](./SYMFONY-AI-BUNDLE-SPRINT1.md)
- [Analyse complète](./docs/symfony-ai-bundle-analysis.md)

---

**Créé le:** 2025-12-30
**Auteur:** Claude Code
**Sprint:** 2/4
**Prochaine révision:** Après Sprint 3
