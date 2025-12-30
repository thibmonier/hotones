# Symfony AI Bundle - Analyse d'opportunité pour HotOnes

**Date:** 2025-12-30
**Version Symfony AI Bundle:** Dernière stable (24 décembre 2025)
**Status:** 📊 Analyse & Recommandation

## 🎯 Résumé exécutif

**Recommandation:** ✅ **Adoption fortement recommandée**

Symfony AI Bundle apporterait des améliorations significatives à votre architecture IA actuelle :
- **Unification** : API unique pour OpenAI, Anthropic, Gemini
- **Agents autonomes** : Tool calling pour des assistants intelligents
- **RAG prêt à l'emploi** : Intégration vector stores pour documentation/contexte
- **Production-ready** : Profiler, monitoring, traçabilité

**ROI estimé :** Gain de 40-60% de temps de développement sur features IA futures

---

## 📊 Comparaison : Architecture actuelle vs Symfony AI Bundle

### Architecture actuelle (src/Service/AiAssistantService.php)

#### ✅ Points forts
- Multi-provider avec fallback (OpenAI → Anthropic → Gemini)
- JSON mode pour réponses structurées
- Méthodes métier spécifiques (`analyzeSentiment`, `generateQuoteLines`)

#### ❌ Limitations identifiées

| Limitation | Impact | Résolu par AI Bundle |
|------------|--------|---------------------|
| **Pas de gestion d'historique** | Conversations sans mémoire | ✅ Message Store (Cache/DB) |
| **Pas de tool calling** | Agents non autonomes | ✅ Toolbox + #[AsTool] |
| **Code dupliqué multi-provider** | Maintenance complexe (173 lignes) | ✅ Platform abstraction unique |
| **Pas de RAG** | Contexte limité, pas de docs | ✅ Vector stores (ChromaDB/Memory) |
| **Parsing JSON manuel** | Erreurs potentielles | ✅ Structured outputs natifs |
| **Pas de profiling** | Debug difficile | ✅ Profiler Symfony intégré |
| **Gestion d'erreurs basique** | Fallback silencieux | ✅ Fault-tolerant toolbox |
| **Pas de streaming** | UX dégradée sur longues réponses | ✅ SSE streaming natif |

### Architecture cible avec Symfony AI Bundle

```yaml
# config/packages/ai.yaml
ai:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'
        gemini:
            api_key: '%env(GEMINI_API_KEY)%'

    agent:
        quote_assistant:
            model: 'gpt-4o-mini'
            platform: 'ai.platform.openai'
            prompt:
                text: |
                    Tu es un assistant expert pour une agence web.
                    Tu aides à générer des devis précis et professionnels.
                include_tools: true
            memory: 'conversation_history'

        sentiment_analyzer:
            model: 'claude-3-5-haiku-20241022'
            platform: 'ai.platform.anthropic'
            prompt:
                text: 'Tu analyses le sentiment client avec précision.'

    chat:
        customer_support:
            agent: 'ai.agent.quote_assistant'
            message_store: 'ai.message_store.cache.support'

    store:
        documentation:
            collection: 'project_docs'

    vectorizer:
        embeddings:
            platform: 'ai.platform.openai'
            model:
                name: 'text-embedding-3-small'
                options:
                    dimensions: 512
```

---

## 🚀 Cas d'usage améliorés

### 1. Assistant de devis avec contexte projet

**Actuel :** `generateQuoteLines()` - prompt simple sans contexte

**Avec AI Bundle :**
```php
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;

#[AsTool('get_client_history', 'Récupère l\'historique projet du client')]
final class ClientHistoryTool
{
    public function __construct(
        private ClientRepository $clientRepository
    ) {}

    public function __invoke(string $clientName): array
    {
        $client = $this->clientRepository->findOneBy(['name' => $clientName]);

        return [
            'previous_projects' => count($client->getProjects()),
            'average_budget' => $client->getAverageBudget(),
            'technologies' => $client->getPreferredTechnologies(),
        ];
    }
}

final readonly class QuoteService
{
    public function __construct(
        private AgentInterface $quoteAssistant,
    ) {}

    public function generateQuote(string $description, Client $client): array
    {
        $messages = new MessageBag(
            Message::forSystem("Client: {$client->getName()}"),
            Message::ofUser("Génère un devis pour : {$description}")
        );

        // L'agent peut appeler get_client_history automatiquement
        $response = $this->quoteAssistant->call($messages);

        return json_decode($response, true);
    }
}
```

**Gain :** Agent autonome qui utilise l'historique client automatiquement

---

### 2. Analyse de sentiment avec historique

**Actuel :** `analyzeSentiment()` - analyse isolée

**Avec AI Bundle :**
```php
use Symfony\AI\Chat\ChatInterface;
use Symfony\AI\Platform\Message\Message;

final readonly class CustomerSupportService
{
    public function __construct(
        private ChatInterface $supportChat,
    ) {}

    public function analyzeWithContext(string $feedback, string $conversationId): array
    {
        // Le chat maintient l'historique automatiquement
        $response = $this->supportChat->complete(
            conversationId: $conversationId,
            message: Message::ofUser("Analyse : {$feedback}")
        );

        // Accès à l'historique complet
        $history = $this->supportChat->getMessages($conversationId);

        return [
            'sentiment' => $response,
            'context_messages' => count($history),
        ];
    }
}
```

**Gain :** Analyse avec contexte conversationnel complet

---

### 3. Recommandations de planning avec RAG

**Actuel :** `PlanningAIAssistant` - prompt statique (304 lignes)

**Avec AI Bundle + RAG :**
```php
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Store\RetrieverInterface;
use Symfony\AI\Platform\Message\Message;

#[AsTool('search_project_docs', 'Recherche dans la documentation projet')]
final class ProjectDocsTool
{
    public function __construct(
        private RetrieverInterface $docsRetriever,
    ) {}

    public function __invoke(string $query): array
    {
        // Recherche sémantique dans vector store
        $results = $this->docsRetriever->retrieve($query, limit: 5);

        return array_map(
            fn($doc) => $doc->getContent(),
            $results
        );
    }
}

final readonly class EnhancedPlanningAssistant
{
    public function __construct(
        private AgentInterface $planningAgent,
    ) {}

    public function optimizePlanning(array $context): array
    {
        $prompt = $this->buildPrompt($context);

        // L'agent peut appeler search_project_docs automatiquement
        $response = $this->planningAgent->call(
            Message::ofUser($prompt)
        );

        return json_decode($response, true);
    }
}
```

**Gain :** Agent avec accès documentaire automatique (best practices, historique)

---

## 🏗️ Plan d'implémentation

### Phase 1 : Installation & Configuration (1 jour)

**Installation :**
```bash
composer require symfony/ai-bundle
composer require symfony/ai-openai-platform
composer require symfony/ai-anthropic-platform
composer require symfony/ai-gemini-platform
```

**Configuration initiale :**
```yaml
# config/packages/ai.yaml
ai:
    platform:
        openai:
            api_key: '%env(OPENAI_API_KEY)%'
        anthropic:
            api_key: '%env(ANTHROPIC_API_KEY)%'
        gemini:
            api_key: '%env(GEMINI_API_KEY)%'

    agent:
        default:
            model: 'gpt-4o-mini'
            platform: 'ai.platform.openai'
            prompt: 'Tu es un assistant expert pour une agence web.'
```

**Tests :**
```bash
php bin/console ai:platform:invoke openai gpt-4o-mini "Test"
php bin/console ai:agent:call default
```

---

### Phase 2 : Migration AiAssistantService (2-3 jours)

**Étape 2.1 : Créer les agents spécialisés**

```yaml
# config/packages/ai.yaml
ai:
    agent:
        sentiment_analyzer:
            model: 'claude-3-5-haiku-20241022'
            platform: 'ai.platform.anthropic'
            prompt:
                text: |
                    Analyse le sentiment du texte suivant issu d'un client.
                    Réponds uniquement en JSON avec les clés:
                    - sentiment: positive, neutral, negative
                    - score: 0-100
                    - summary: court résumé

        email_responder:
            model: 'gpt-4o-mini'
            platform: 'ai.platform.openai'
            prompt:
                text: 'Rédige une réponse professionnelle et empathique à cet email client.'

        quote_generator:
            model: 'gpt-4o-mini'
            platform: 'ai.platform.openai'
            prompt:
                text: |
                    Génère une liste de lignes de devis (tâches) estimées pour ce projet web.
                    Format JSON : liste d'objets avec 'title', 'description', 'days' (jours/homme).
```

**Étape 2.2 : Créer le nouveau service unifié**

```php
<?php

declare(strict_types=1);

namespace App\Service\AI;

use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service d'assistant IA unifié utilisant Symfony AI Bundle.
 *
 * @deprecated Use specific agents directly via DI
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

    /**
     * Analyse un sentiment client (positif, neutre, négatif).
     */
    public function analyzeSentiment(string $text): array
    {
        $response = $this->sentimentAgent->call(
            Message::ofUser("Texte : \"{$text}\"")
        );

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * Génère une suggestion de réponse à un email.
     */
    public function generateEmailReply(string $incomingEmail, string $context = ''): string
    {
        $prompt = "Contexte additionnel : {$context}. Email reçu : \"{$incomingEmail}\"";

        return $this->emailAgent->call(
            Message::ofUser($prompt)
        );
    }

    /**
     * Génère des lignes de devis à partir d'une description.
     */
    public function generateQuoteLines(string $projectDescription): array
    {
        $response = $this->quoteAgent->call(
            Message::ofUser("Projet : \"{$projectDescription}\"")
        );

        return json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    }
}
```

**Étape 2.3 : Tests de régression**

```bash
docker compose exec app composer test -- --filter=AiAssistantService
```

---

### Phase 3 : Améliorer avec Tools (3-4 jours)

**Créer des outils métier :**

```php
// src/AI/Tool/ClientHistoryTool.php
namespace App\AI\Tool;

use App\Repository\ClientRepository;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('get_client_history', 'Récupère l\'historique de projets d\'un client')]
final readonly class ClientHistoryTool
{
    public function __construct(
        private ClientRepository $clientRepository,
    ) {}

    public function __invoke(string $clientName): array
    {
        $client = $this->clientRepository->findOneBy(['name' => $clientName]);

        if (!$client) {
            return ['error' => 'Client not found'];
        }

        return [
            'client_name' => $client->getName(),
            'total_projects' => count($client->getProjects()),
            'average_budget' => $client->getAverageBudget(),
            'preferred_technologies' => $client->getPreferredTechnologies(),
            'satisfaction_score' => $client->getSatisfactionScore(),
        ];
    }
}

// src/AI/Tool/ProjectStatsTool.php
#[AsTool('get_project_stats', 'Récupère les statistiques d\'un type de projet')]
final readonly class ProjectStatsTool
{
    public function __construct(
        private ProjectRepository $projectRepository,
    ) {}

    public function __invoke(string $projectType): array
    {
        return [
            'average_duration' => $this->projectRepository->getAverageDuration($projectType),
            'average_team_size' => $this->projectRepository->getAverageTeamSize($projectType),
            'success_rate' => $this->projectRepository->getSuccessRate($projectType),
        ];
    }
}

// src/AI/Tool/CompanyInfoTool.php
#[AsTool('get_company_info', 'Récupère les informations de l\'entreprise')]
final readonly class CompanyInfoTool
{
    public function __construct(
        private CompanySettingsRepository $settingsRepo,
    ) {}

    public function __invoke(): array
    {
        $settings = $this->settingsRepo->findFirst();

        return [
            'company_name' => $settings->getCompanyName(),
            'default_tjm' => $settings->getDefaultTjm(),
            'hourly_rate' => $settings->getHourlyRate(),
        ];
    }
}
```

**Mettre à jour la config agent :**

```yaml
# config/packages/ai.yaml
ai:
    agent:
        quote_generator:
            model: 'gpt-4o-mini'
            platform: 'ai.platform.openai'
            prompt:
                text: |
                    Tu es un expert en estimation de projets web.
                    Utilise les outils disponibles pour générer des devis précis.
                include_tools: true  # Active tool calling
```

**Résultat :** L'agent appelle automatiquement `get_client_history`, `get_project_stats`, etc.

---

### Phase 4 : Ajouter RAG pour documentation (4-5 jours)

**Installation vector store :**

```bash
composer require symfony/ai-chromadb-store
# Ou pour démarrer simple :
# composer require symfony/ai-memory-store
```

**Configuration :**

```yaml
# config/packages/ai.yaml
ai:
    store:
        chromadb:
            documentation:
                host: '%env(CHROMADB_HOST)%'  # http://chromadb:8000
                collection: 'hotones_docs'
        memory:
            cache:
                strategy: 'cosine'

    vectorizer:
        openai_embeddings:
            platform: 'ai.platform.openai'
            model:
                name: 'text-embedding-3-small'
                options:
                    dimensions: 512

    indexer:
        docs:
            loader: 'Symfony\AI\Store\Document\Loader\TextFileLoader'
            vectorizer: 'ai.vectorizer.openai_embeddings'
            store: 'ai.store.chromadb.documentation'

    retriever:
        docs:
            vectorizer: 'ai.vectorizer.openai_embeddings'
            store: 'ai.store.chromadb.documentation'
```

**Indexer la documentation :**

```bash
# Setup du store
php bin/console ai:store:setup chromadb.documentation

# Indexer les docs
php bin/console ai:store:index docs --source=docs/
php bin/console ai:store:index docs --source=CLAUDE.md
php bin/console ai:store:index docs --source=docs/profitability.md
```

**Créer un outil de recherche :**

```php
namespace App\AI\Tool;

use Symfony\AI\Store\RetrieverInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;

#[AsTool('search_documentation', 'Recherche dans la documentation technique HotOnes')]
final readonly class DocumentationSearchTool
{
    public function __construct(
        private RetrieverInterface $docsRetriever,
    ) {}

    public function __invoke(string $query): array
    {
        $documents = $this->docsRetriever->retrieve($query, limit: 5);

        return array_map(
            fn($doc) => [
                'content' => $doc->getContent(),
                'metadata' => $doc->getMetadata(),
                'score' => $doc->getScore(),
            ],
            $documents
        );
    }
}
```

**Résultat :** Agent avec accès à toute la documentation technique

---

### Phase 5 : Monitoring & Profiling (1-2 jours)

**Activer le profiler :**

```yaml
# config/packages/dev/ai.yaml
ai:
    profiler: true
```

**Dashboard Profiler :**
- ✅ Voir toutes les requêtes IA dans le Symfony Profiler
- ✅ Token usage par requête
- ✅ Latence et performance
- ✅ Tools appelés et leurs résultats
- ✅ Messages échangés

**Logging :**

```yaml
# config/packages/monolog.yaml
monolog:
    channels: ['ai']
    handlers:
        ai:
            type: stream
            path: '%kernel.logs_dir%/ai.log'
            level: info
            channels: ['ai']
```

---

## 📈 Bénéfices attendus

### Gains techniques

| Métrique | Avant | Après | Amélioration |
|----------|-------|-------|--------------|
| **Lignes de code IA** | ~477 lignes | ~150 lignes | **-68%** |
| **Support multi-provider** | Custom (173 lignes) | Config YAML | **-100% code** |
| **Tool calling** | ❌ Non | ✅ Natif | **+∞** |
| **RAG capabilities** | ❌ Non | ✅ Natif | **+∞** |
| **Message history** | ❌ Non | ✅ Cache/DB | **+∞** |
| **Debugging** | Logs manuels | Profiler Symfony | **Meilleur** |
| **Streaming** | ❌ Non | ✅ SSE | **+∞** |

### Nouveaux cas d'usage possibles

1. **Assistant conversationnel client** avec historique complet
2. **Génération de devis contextualisée** avec historique client
3. **Chatbot support technique** avec accès à la documentation
4. **Analyse prédictive** basée sur données historiques
5. **Recommandations de planning** avec contexte projet
6. **Génération de rapports** avec accès aux métriques

### Coût de développement

| Feature | Sans AI Bundle | Avec AI Bundle | Gain temps |
|---------|---------------|----------------|------------|
| Chat avec historique | 2-3 jours | 2 heures | **-85%** |
| Tool calling custom | 3-4 jours | 1 heure | **-95%** |
| RAG/Vector search | 5-7 jours | 3-4 heures | **-90%** |
| Multi-agent routing | 4-5 jours | 2 heures | **-95%** |
| Profiling/Debug | 2-3 jours | Inclus | **-100%** |

---

## ⚠️ Considérations

### Limitations actuelles

1. **Bundle expérimental**
   - Pas couvert par Backward Compatibility Promise
   - API peut changer entre versions
   - **Mitigation :** Tester à chaque upgrade, figer version en prod

2. **Dépendance ChromaDB**
   - Nécessite conteneur Docker supplémentaire pour RAG
   - **Alternative :** Utiliser Memory store pour commencer

3. **Courbe d'apprentissage**
   - Nouveaux concepts (Agents, Tools, Retrievers)
   - **Mitigation :** Documentation excellente, exemples fournis

### Risques

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| Breaking changes | Moyen | Moyenne | Figer version, tests |
| Performance ChromaDB | Faible | Faible | Memory store fallback |
| Complexité accrue | Faible | Faible | Formation équipe |
| Coût tokens augmenté | Moyen | Moyenne | Rate limiting, monitoring |

---

## 🎯 Recommandation finale

### ✅ Adopter Symfony AI Bundle MAINTENANT

**Raisons :**
1. ✅ **ROI immédiat** : Simplification architecture existante (-68% de code)
2. ✅ **Future-proof** : Standard Symfony pour IA
3. ✅ **Capacités accrues** : Tool calling, RAG, multi-agent
4. ✅ **Productivité** : Réduction 80-90% temps dev features IA
5. ✅ **Qualité** : Profiler, debugging, monitoring

**Plan d'action recommandé :**

**Sprint 1 (1 semaine) :**
- Phase 1 : Installation & configuration
- Phase 2 : Migration AiAssistantService
- Tests de régression

**Sprint 2 (1 semaine) :**
- Phase 3 : Création tools métier (client history, project stats)
- Tests sur génération de devis améliorée

**Sprint 3 (1 semaine) :**
- Phase 4 : Setup RAG avec docs techniques
- Création chatbot support interne

**Sprint 4 (1 semaine) :**
- Phase 5 : Monitoring & profiling
- Formation équipe
- Documentation interne

**Total estimé :** 4 semaines pour migration complète + features avancées

---

## 📚 Ressources

### Documentation officielle
- [Symfony AI Bundle](https://symfony.com/doc/current/ai/bundles/ai-bundle.html)
- [Symfony AI Platform](https://symfony.com/doc/current/ai/components/platform.html)
- [GitHub symfony/ai](https://github.com/symfony/ai)
- [GitHub symfony/ai-bundle](https://github.com/symfony/ai-bundle)

### Tutoriels
- [Building AI-Driven Features in Symfony](https://sensiolabs.com/blog/2025/building-ai-driven-features-in-symfony)
- [Symfony AI Initiative](https://symfony.com/blog/kicking-off-the-symfony-ai-initiative)

### Outils additionnels
```bash
composer require symfony/ai-brave-tool        # Web search
composer require symfony/ai-wikipedia-tool    # Wikipedia
composer require symfony/ai-tavily-tool       # Advanced search
composer require symfony/ai-youtube-tool      # YouTube info
```

---

**Créé le :** 2025-12-30
**Auteur :** Claude Code
**Version :** 1.0
**Prochaine révision :** Après Sprint 1
