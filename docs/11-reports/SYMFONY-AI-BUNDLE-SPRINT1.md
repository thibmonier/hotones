# Symfony AI Bundle - Sprint 1

**Date:** 2025-12-30
**Status:** ✅ Complété
**Durée:** ~2h

## 📋 Résumé

Sprint 1 de l'adoption du Symfony AI Bundle : installation, configuration initiale et migration du service `AiAssistantService` vers la nouvelle architecture basée sur les agents Symfony AI.

## 🎯 Objectifs atteints

- ✅ Installation des bundles Symfony AI (bundle, platform, agent)
- ✅ Configuration des 3 plateformes AI (OpenAI, Anthropic, Gemini)
- ✅ Création de 3 agents spécialisés (sentiment, email, quote)
- ✅ Migration du service AiAssistantService
- ✅ Tests de régression (458 tests passent)

## 📦 Packages installés

```json
"symfony/ai-bundle": "^0.1.0",
"symfony/ai-platform": "^0.1.0",
"symfony/ai-agent": "^0.1.0",
"symfony/ai-open-ai-platform": "^0.1.0",
"symfony/ai-anthropic-platform": "^0.1.0",
"symfony/ai-gemini-platform": "^0.1.0"
```

**Note:** Tous les packages sont en version 0.1.0 (expérimental)

## 🔧 Configuration

### Fichier `.env` modifié

Les clés API ne sont plus définies vides dans `.env` pour éviter les conflits avec `.env.dev.local` :

```diff
###> AI Configuration ###
# OpenAI API Key
- OPENAI_API_KEY=
+ # OPENAI_API_KEY= # Defined in .env.dev.local
# Anthropic API Key
- ANTHROPIC_API_KEY=
+ # ANTHROPIC_API_KEY= # Defined in .env.dev.local
# Gemini API Key
- GEMINI_API_KEY=
+ # GEMINI_API_KEY= # Defined in .env.dev.local
###< AI Configuration ###
```

**Important:** Dans l'environnement Docker, les variables AI doivent être définies dans `.env.dev.local` (pas `.env.local`) pour être correctement chargées par Symfony Runtime.

### Fichier `config/packages/ai.yaml` créé

```yaml
ai:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'
        gemini:
            api_key: '%env(GEMINI_API_KEY)%'

    agent:
        # Agent d'analyse de sentiment client
        sentiment_analyzer:
            platform: 'ai.platform.anthropic'
            model: 'claude-3-5-haiku-20241022'
            prompt:
                text: |
                    Analyse le sentiment du texte suivant issu d'un client.
                    Réponds uniquement en JSON avec les clés:
                    - sentiment: positive, neutral, negative
                    - score: 0-100
                    - summary: court résumé

        # Agent de génération de réponse email
        email_responder:
            platform: 'ai.platform.openai'
            model: 'gpt-4o-mini'
            prompt:
                text: |
                    Rédige une réponse professionnelle et empathique à cet email client.

        # Agent de génération de lignes de devis
        quote_generator:
            platform: 'ai.platform.openai'
            model: 'gpt-4o-mini'
            prompt:
                text: |
                    Génère une liste de lignes de devis pour ce projet web.
                    Format JSON : liste d'objets avec 'title', 'description', 'days'.
```

### Nouveau service `src/Service/AI/AiAssistantService.php`

```php
<?php

namespace App\Service\AI;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

/**
 * Service d'assistant IA unifié utilisant Symfony AI Bundle.
 */
final readonly class AiAssistantService
{
    public function __construct(
        #[Autowire(service: 'ai.agent.sentiment_analyzer')]
        private AgentInterface $sentimentAgent,

        #[Autowire(service: 'ai.agent.email_responder')]
        private AgentInterface $emailAgent,

        #[Autowire(service: 'ai.agent.quote_generator')]
        private AgentInterface $quoteAgent,
    ) {}

    public function analyzeSentiment(string $text): array;
    public function generateEmailReply(string $incomingEmail, string $context = ''): string;
    public function generateQuoteLines(string $projectDescription): array;
}
```

## 📊 Comparaison avant/après

| Métrique | Avant | Après | Amélioration |
|----------|-------|-------|--------------|
| Lignes de code | 173 lignes | 78 lignes | **-55%** |
| Dépendances | `openai-php/client`<br>`anthropic-ai/sdk` | `symfony/ai-*` | Unifié |
| Configuration | Code PHP hardcodé | YAML déclaratif | ✅ |
| Multi-provider | Fallback manuel | Intégré | ✅ |
| Profiler | ❌ Non | ✅ Oui (dev) | ✅ |

## 🐛 Problèmes rencontrés et solutions

### 1. Variables d'environnement non chargées

**Problème:** Les clés API définies dans `.env.local` n'étaient pas chargées par Symfony.

**Cause:** Dans un conteneur Docker avec `APP_ENV=dev`, Symfony Runtime charge `.env.dev.local` en priorité, pas `.env.local`.

**Solution:** Créer `.env.dev.local` avec les clés API au lieu de `.env.local`.

```bash
# Solution
cp .env.local .env.dev.local
```

### 2. Conflit avec définitions vides dans .env

**Problème:** Les variables vides dans `.env` empêchaient l'override par `.env.dev.local`.

**Solution:** Commenter les définitions vides dans `.env` de base.

## 🧪 Tests

```bash
composer test
```

**Résultat:** ✅ 458 tests, 1410 assertions - Tous passent

## 📚 Fichiers modifiés

- ✅ `composer.json` - Ajout des packages Symfony AI
- ✅ `composer.lock` - Verrouillage des versions
- ✅ `.env` - Commentaire des clés API vides
- ✅ `config/packages/ai.yaml` - Nouveau fichier de config
- ✅ `config/services.yaml` - Ajout paramètres AI (optionnel)
- ✅ `config/bundles.php` - Ajout AiBundle (automatique)
- ✅ `src/Service/AI/AiAssistantService.php` - Nouveau service
- ❌ `src/Service/AiAssistantService.php` - Supprimé (ancien)

## 🚀 Prochaines étapes (Sprint 2)

1. **Création de Tools métier** (3-4 jours)
   - `ClientHistoryTool` - Récupérer historique client
   - `ProjectStatsTool` - Statistiques de projets
   - `CompanyInfoTool` - Informations entreprise

2. **Amélioration génération de devis**
   - Intégration tools dans `quote_generator`
   - Tests avec données réelles

## 📝 Notes importantes

1. **Bundle expérimental:** Symfony AI Bundle est en v0.1.0, pas couvert par BC Promise
2. **API keys:** Stockées dans `.env.dev.local` (Docker dev) ou variables d'env (production)
3. **Profiler:** Activé automatiquement en mode dev pour traçabilité IA

## 🔗 Documentation

- [Symfony AI Bundle](https://symfony.com/doc/current/ai/bundles/ai-bundle.html)
- [Analyse d'opportunité](./docs/symfony-ai-bundle-analysis.md)
- [Plan d'implémentation complet](./docs/symfony-ai-bundle-analysis.md#plan-dimplémentation)

---

**Créé le:** 2025-12-30
**Auteur:** Claude Code
**Sprint:** 1/4
**Prochaine révision:** Après Sprint 2
