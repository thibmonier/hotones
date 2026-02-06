# Mago - Modern PHP Code Quality Tool

## Vue d'ensemble

Mago est un outil moderne de qualité de code PHP qui combine trois fonctionnalités principales :
- **Format** : Formatage du code (PSR-12 + Symfony)
- **Lint** : Vérification de qualité du code
- **Analyze** : Analyse statique (niveau PHPStan)

## Installation

Mago est installé via Composer comme dépendance de développement :

```bash
composer require --dev carthage-software/mago
```

## Configuration

La configuration se trouve dans `mago.yaml` à la racine du projet. Elle est alignée avec les autres outils de qualité (PHP-CS-Fixer, PHPStan, PHP_CodeSniffer).

### Chemins analysés

```yaml
paths:
    - src
    - tests

exclude:
    - var
    - vendor
    - migrations
    - public
    - src/Entity  # Temporairement exclu (property hooks PHP 8.5)
```

### Règles de formatage

Aligned with PHP-CS-Fixer:
- PSR-12 standard
- Symfony conventions
- Short array syntax
- Binary operators alignment
- No unused imports
- Ordered imports (alphabetical)
- Trailing commas in multiline
- No Yoda style

### Règles de linting

- Cyclomatic complexity: max 15
- Line length: max 150 characters
- Nesting level: max 7
- Unused variables detection
- Undefined variables detection

### Analyse statique

- Level 4 (aligned with PHPStan)
- Doctrine support
- Symfony support
- Type checking with PHPDoc (not as certain)

## Utilisation

### Via Composer (recommandé)

```bash
# Toutes les vérifications Mago
docker compose exec app composer mago

# Vérification du formatage uniquement
docker compose exec app composer mago-format

# Auto-fix du formatage
docker compose exec app composer mago-format-fix

# Linting uniquement
docker compose exec app composer mago-lint

# Analyse statique uniquement
docker compose exec app composer mago-analyze
```

### Directement avec Mago CLI

```bash
# Format check (dry-run)
docker compose exec app vendor/bin/mago format --dry-run

# Format fix
docker compose exec app vendor/bin/mago format

# Lint
docker compose exec app vendor/bin/mago lint

# Analyze
docker compose exec app vendor/bin/mago analyze

# Avec format de sortie GitHub (pour CI)
docker compose exec app vendor/bin/mago lint --reporting-format=github
docker compose exec app vendor/bin/mago analyze --reporting-format=github
```

## Intégration CI/CD

Mago est intégré dans les workflows GitHub Actions :

### CI Principal (ci.yml)

Job dédié `mago` qui s'exécute en parallèle des autres checks :
- Format check (dry-run)
- Lint
- Analyze

Le job est configuré avec `continue-on-error: true` initialement pour ne pas bloquer le CI pendant la phase de transition.

### Quality Workflow (quality.yml)

Mago est inclus dans la matrice des outils de qualité qui s'exécutent quotidiennement à 6h du matin.

## Comparaison avec les autres outils

| Outil | Fonction principale | Niveau |
|-------|---------------------|--------|
| PHP-CS-Fixer | Code style (PSR-12 + Symfony) | Style |
| PHPStan | Analyse statique (types, bugs) | Level 4 |
| PHP_CodeSniffer | Code quality (complexity, metrics) | Quality |
| **Mago** | **Tout-en-un (format + lint + analyze)** | **Modern** |
| Deptrac | Architecture (layering, dependencies) | Architecture |
| Infection | Mutation testing (test quality) | Testing |

### Avantages de Mago

✅ **Moderne** : Support natif PHP 8.5 (property hooks)
✅ **Tout-en-un** : Format + Lint + Analyze en un seul outil
✅ **Performant** : Traitement parallèle optimisé
✅ **Cohérent** : Configuration centralisée dans un seul fichier
✅ **Extensible** : Peut potentiellement remplacer PHP-CS-Fixer et PHPStan

### Stratégie de migration

1. **Phase 1 (actuelle)** : Mago en parallèle des outils existants (`continue-on-error: true`)
2. **Phase 2** : Validation et ajustement de la configuration Mago
3. **Phase 3** : Mago devient bloquant (`continue-on-error: false`)
4. **Phase 4** : Évaluation du remplacement de PHP-CS-Fixer et/ou PHPStan par Mago

## Scripts Composer

```json
{
    "scripts": {
        "mago-format": "mago format --dry-run",
        "mago-format-fix": "mago format",
        "mago-lint": "mago lint",
        "mago-analyze": "mago analyze",
        "mago": [
            "@mago-format",
            "@mago-lint",
            "@mago-analyze"
        ],
        "check-code": [
            "@phpcsfixer",
            "@phpstan",
            "@phpcs",
            "@mago"
        ]
    }
}
```

## Ressources

- **Documentation officielle** : [mago.gitbook.io/mago](https://mago.gitbook.io/mago)
- **Repository GitHub** : [carthage-software/mago](https://github.com/carthage-software/mago)
- **Configuration projet** : `mago.yaml`

## Dépannage

### Erreur "property hooks not supported"

Les property hooks PHP 8.4/8.5 sont temporairement exclues :

```yaml
exclude:
    - src/Entity
```

Cette exclusion sera retirée lorsque Mago supportera complètement cette syntaxe.

### Conflits avec PHP-CS-Fixer

Si Mago et PHP-CS-Fixer proposent des corrections différentes, privilégier Mago (plus moderne). Ajuster la config de PHP-CS-Fixer si nécessaire.

### Performance

Le traitement parallèle peut être ajusté dans `mago.yaml` :

```yaml
parallel:
    enabled: true
    max_workers: 4  # Ajuster selon les ressources disponibles
```

---

**Dernière mise à jour** : 2025-02-05
**Version Mago** : ^1.4.1
