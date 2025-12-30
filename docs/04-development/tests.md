# 🧪 Tests

Cette application dispose d’une batterie de tests couvrant plusieurs niveaux.

## Types de tests
- Unitaires: Services et entités (calculs, formatage...)
- Intégration: Repositories Doctrine (requêtes, agrégats)
- Fonctionnels: Contrôleurs HTTP (WebTestCase) avec Foundry
- E2E (navigateur): Panther (Chrome headless)

## Exécution
```bash
# Tous les tests
./vendor/bin/phpunit

# Depuis Docker
docker compose exec app ./vendor/bin/phpunit
```

## Environnement de test
- `.env.test` configure une base SQLite locale pour des tests isolés.
- Les schémas sont (ré)initialisés automatiquement via ResetDatabase (Foundry).

## E2E avec Panther
Prérequis: Google Chrome/Chromium installé.

Variables utiles:
```bash
# Si nécessaire, pointer vers Chrome
export PANTHER_CHROME_BINARY="/Applications/Google Chrome.app/Contents/MacOS/Google Chrome"
# Désactiver le sandbox si requis
export PANTHER_NO_SANDBOX=1
```

Lancement:
```bash
./vendor/bin/phpunit --testsuite default
```

## Intégration continue
Un workflow GitHub Actions exécute:
- PHPUnit (incluant E2E avec Chrome headless)
- Qualité: php-cs-fixer (dry-run), phpstan, phpmd

Voir: `.github/workflows/ci.yml`.
