# Intelligence Artificielle pour l'Optimisation du Planning

Ce document explique comment configurer et utiliser l'intelligence artificielle pour améliorer les recommandations d'optimisation du planning.

## Vue d'ensemble

Le système d'optimisation du planning intègre l'IA pour :
- Analyser les situations complexes de charge de travail
- Générer des recommandations intelligentes et contextuelles
- Prioriser les actions selon le contexte métier
- Fournir des insights sur les tendances d'équipe

## Configuration

### 1. Obtenir une clé API OpenAI

1. Créez un compte sur [OpenAI Platform](https://platform.openai.com/)
2. Accédez à [API Keys](https://platform.openai.com/api-keys)
3. Créez une nouvelle clé API
4. Copiez la clé (elle ne sera affichée qu'une seule fois)

### 2. Configurer l'application

Ajoutez votre clé API dans le fichier `.env.local` :

```bash
###> AI Configuration ###
OPENAI_API_KEY=sk-proj-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
###< AI Configuration ###
```

**Important :**
- Ne commitez JAMAIS vos clés API dans le repository
- Utilisez `.env.local` pour les configurations locales
- En production, utilisez les variables d'environnement du serveur

### 3. Vérifier l'activation

L'IA s'active automatiquement dès qu'une clé API valide est configurée.
Une bannière verte apparaîtra sur la page `/planning/optimization` indiquant que l'IA est active.

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

## Modèle utilisé

Par défaut, le système utilise **GPT-4o-mini** :
- Modèle rapide et économique
- Excellente qualité de réponse
- Coût : ~0,15 $ par million de tokens d'entrée

Pour changer de modèle, modifiez le code dans `src/Service/Planning/AI/PlanningAIAssistant.php` :

```php
$response = $client->chat()->create([
    'model' => 'gpt-4o', // ou 'gpt-4', 'gpt-3.5-turbo', etc.
    // ...
]);
```

## Coûts estimés

Estimation pour un usage typique :

| Action | Tokens | Coût approximatif |
|--------|--------|------------------|
| Analyse d'une équipe de 10 personnes | ~1500 | $0.0002 |
| Génération de 5 recommandations | ~2000 | $0.0003 |
| Usage mensuel (100 analyses) | ~350k | $0.05 |

**Note :** Les coûts réels dépendent de la taille de votre équipe et de la fréquence d'utilisation.

## Support Anthropic Claude (à venir)

Le système est préparé pour supporter Anthropic Claude :

1. Installez le SDK Anthropic :
```bash
composer require anthropic/sdk
```

2. Configurez la clé API :
```bash
ANTHROPIC_API_KEY=sk-ant-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx
```

3. Implémentez la méthode `callAnthropic()` dans `PlanningAIAssistant.php`

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

- [OpenAI API Documentation](https://platform.openai.com/docs/api-reference)
- [OpenAI PHP Client](https://github.com/openai-php/client)
- [Pricing OpenAI](https://openai.com/pricing)
- [Best Practices OpenAI](https://platform.openai.com/docs/guides/production-best-practices)

## Support

En cas de problème :
1. Consultez les logs Symfony : `var/log/dev.log`
2. Vérifiez le dashboard OpenAI pour les erreurs API
3. Contactez l'équipe de développement
