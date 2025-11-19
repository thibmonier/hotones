# Intelligence Artificielle pour l'Optimisation du Planning

Ce document explique comment configurer et utiliser l'intelligence artificielle pour améliorer les recommandations d'optimisation du planning.

## Vue d'ensemble

Le système d'optimisation du planning intègre l'IA pour :
- Analyser les situations complexes de charge de travail
- Générer des recommandations intelligentes et contextuelles
- Prioriser les actions selon le contexte métier
- Fournir des insights sur les tendances d'équipe

## Configuration

### Option 1 : OpenAI (GPT-4)

#### 1.1. Obtenir une clé API OpenAI

1. Créez un compte sur [OpenAI Platform](https://platform.openai.com/)
2. Accédez à [API Keys](https://platform.openai.com/api-keys)
3. Créez une nouvelle clé API
4. Copiez la clé (elle ne sera affichée qu'une seule fois)

#### 1.2. Configurer l'application

Ajoutez votre clé API dans le fichier `.env.local` :

```bash
###> AI Configuration ###
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
###< AI Configuration ###
```

### Option 2 : Anthropic (Claude)

#### 2.1. Obtenir une clé API Anthropic

1. Créez un compte sur [Anthropic Console](https://console.anthropic.com/)
2. Accédez à [API Keys](https://console.anthropic.com/settings/keys)
3. Créez une nouvelle clé API
4. Copiez la clé (elle ne sera affichée qu'une seule fois)

#### 2.2. Configurer l'application

Ajoutez votre clé API dans le fichier `.env.local` :

```bash
###> AI Configuration ###
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
###< AI Configuration ###
```

### Configuration générale

**Important :**
- Ne commitez JAMAIS vos clés API dans le repository
- Utilisez `.env.local` pour les configurations locales
- En production, utilisez les variables d'environnement du serveur
- Si les deux clés sont configurées, **OpenAI sera utilisé en priorité**
- Pour forcer l'utilisation d'Anthropic, laissez `OPENAI_API_KEY` vide

### Vérifier l'activation

L'IA s'active automatiquement dès qu'une clé API valide est configurée.
Une bannière verte apparaîtra sur la page `/planning/optimization` indiquant que l'IA est active et quel provider est utilisé (OpenAI ou Anthropic).

## Utilisation

### Accès aux recommandations enrichies

1. Connectez-vous avec un compte ayant le rôle `ROLE_MANAGER`
2. Accédez à **Delivery > Optimisation** dans le menu
3. Le système affiche :
   - Les recommandations de base (toujours disponibles)
   - Les recommandations enrichies par l'IA (si configurée)
   - Un indicateur du provider utilisé (OpenAI)

### Ce que l'IA apporte

#### Recommandations de base (sans IA)
- Détection automatique des surcharges et sous-utilisations
- Suggestions de réaffectation basées sur les profils métier
- Prise en compte des niveaux de service client (VIP, Priority, etc.)

#### Recommandations enrichies (avec IA)
- Analyse contextuelle des situations complexes
- Recommandations personnalisées selon l'historique
- Priorisation intelligente des actions
- Insights sur les tendances d'équipe
- Suggestions d'optimisation à moyen terme

## Modèles utilisés

### OpenAI

Par défaut, le système utilise **GPT-4o-mini** :
- Modèle rapide et économique
- Excellente qualité de réponse
- Coût : ~0,15 $ par million de tokens d'entrée
- Limite : 2000 tokens par réponse

Pour changer de modèle OpenAI, modifiez le code dans `src/Service/Planning/AI/PlanningAIAssistant.php` :

```php
$response = $client->chat()->create([
    'model' => 'gpt-4o', // ou 'gpt-4', 'gpt-4o-mini', etc.
    // ...
]);
```

### Anthropic Claude

Le système utilise **Claude 3.5 Haiku** :
- Modèle le plus rapide et économique de la famille Claude 3.5
- Excellente qualité de réponse avec un prix compétitif
- Coût : ~0,80 $ par million de tokens d'entrée / ~4,00 $ par million de tokens de sortie
- Limite : 2000 tokens par réponse

Pour changer de modèle Claude, modifiez le code dans `src/Service/Planning/AI/PlanningAIAssistant.php` :

```php
$response = $client->messages->create(
    model: 'claude-3-5-sonnet-20241022', // ou 'claude-3-opus-20240229', etc.
    // ...
);
```

## Coûts estimés

### OpenAI (GPT-4o-mini)

Estimation pour un usage typique :

| Action | Tokens | Coût approximatif |
|--------|--------|------------------|
| Analyse d'une équipe de 10 personnes | ~1500 | $0.0002 |
| Génération de 5 recommandations | ~2000 | $0.0003 |
| Usage mensuel (100 analyses) | ~350k | $0.05 |

### Anthropic (Claude 3.5 Haiku)

Estimation pour un usage typique :

| Action | Tokens entrée/sortie | Coût approximatif |
|--------|---------------------|------------------|
| Analyse d'une équipe de 10 personnes | 1500/500 | $0.003 |
| Génération de 5 recommandations | 2000/500 | $0.004 |
| Usage mensuel (100 analyses) | 350k/50k | $0.48 |

**Note :** Les coûts réels dépendent de la taille de votre équipe et de la fréquence d'utilisation. Claude est environ 10x plus cher que GPT-4o-mini, mais offre une qualité de réponse excellente.

## Choix du provider

Les deux providers sont entièrement fonctionnels et prêts à l'emploi :

### Quand utiliser OpenAI (GPT-4o-mini) ?
- ✅ Budget limité (~10x moins cher que Claude)
- ✅ Rapidité importante
- ✅ Bon pour des analyses fréquentes
- ✅ Recommandations qualitatives suffisantes

### Quand utiliser Anthropic (Claude 3.5 Haiku) ?
- ✅ Besoin de réponses plus nuancées
- ✅ Analyses complexes nécessitant plus de contexte
- ✅ Budget plus flexible
- ✅ Préférence pour l'éthique et la transparence d'Anthropic

## Dépannage

### L'IA n'est pas active

**Vérifications :**
1. La clé API est bien configurée dans `.env.local`
2. La clé n'est pas vide (pas juste `OPENAI_API_KEY=`)
3. Le cache a été vidé : `php bin/console cache:clear`
4. La clé API est valide sur OpenAI Platform

### Erreur "API key invalid"

- Vérifiez que la clé est correcte
- Assurez-vous d'avoir des crédits sur votre compte OpenAI
- Vérifiez que la clé n'est pas expirée ou révoquée

### Erreur "Rate limit exceeded"

- Vous avez atteint la limite de requêtes par minute
- Attendez quelques minutes avant de réessayer
- Envisagez de passer à un plan supérieur sur OpenAI

### Erreur "Failed to parse AI response as JSON"

- Le modèle a retourné une réponse invalide
- Réessayez l'opération
- Si le problème persiste, vérifiez les logs pour plus de détails

## Sécurité et confidentialité

### Données envoyées à OpenAI

Le système envoie uniquement :
- Les noms des contributeurs
- Les taux d'occupation (TACE en %)
- Le nombre de projets actifs
- **Aucune donnée client sensible**
- **Aucun nom de projet**
- **Aucune donnée financière**

### Bonnes pratiques

1. **Ne pas inclure de données sensibles** dans les noms de contributeurs
2. **Utiliser des comptes de service** dédiés pour les clés API
3. **Limiter les quotas** sur OpenAI Platform pour contrôler les coûts
4. **Monitorer l'usage** via le dashboard OpenAI
5. **Révoquer les clés** en cas de compromission

## Architecture technique

```
PlanningOptimizer (Service de base)
    │
    ├─> TaceAnalyzer (Analyse TACE)
    │   └─> Détection surcharges/sous-utilisations
    │
    └─> PlanningAIAssistant (Enrichissement IA)
        ├─> OpenAI GPT-4o-mini
        └─> (Future) Anthropic Claude
```

### Flux de données

1. L'utilisateur accède à `/planning/optimization`
2. `PlanningOptimizer` génère les recommandations de base
3. Si l'IA est configurée, `PlanningAIAssistant` enrichit les recommandations
4. Les résultats sont affichés avec un indicateur du provider utilisé

## Développement

### Ajouter un nouveau provider

1. Créez une méthode `callProviderName()` dans `PlanningAIAssistant`
2. Ajoutez la logique dans `callAI()` pour router vers le bon provider
3. Configurez la clé API dans `.env`
4. Documentez dans ce fichier

### Tests

Les tests de l'intégration IA sont dans `tests/Integration/Service/Planning/AI/`

Pour tester sans vraie clé API, utilisez un mock :

```php
$assistant = new PlanningAIAssistant(
    openaiApiKey: 'test-key-mock',
    anthropicApiKey: null
);
```

## Ressources

### OpenAI
- [OpenAI API Documentation](https://platform.openai.com/docs/api-reference)
- [OpenAI PHP Client](https://github.com/openai-php/client)
- [Pricing OpenAI](https://openai.com/pricing)
- [Best Practices OpenAI](https://platform.openai.com/docs/guides/production-best-practices)

### Anthropic
- [Anthropic API Documentation](https://docs.anthropic.com/en/api/getting-started)
- [Anthropic PHP SDK](https://github.com/anthropic-ai/anthropic-sdk-php)
- [Pricing Anthropic](https://www.anthropic.com/pricing#anthropic-api)
- [Claude Model Comparison](https://docs.anthropic.com/en/docs/about-claude/models)

## Support

En cas de problème :
1. Consultez les logs Symfony : `var/log/dev.log`
2. Vérifiez le dashboard OpenAI pour les erreurs API
3. Contactez l'équipe de développement
