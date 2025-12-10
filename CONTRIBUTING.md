# Contributing to HotOnes

Merci de votre intérêt pour contribuer à HotOnes ! Ce document fournit les guidelines pour contribuer au projet.

## 📋 Table des matières

- [Code de conduite](#code-de-conduite)
- [Comment contribuer](#comment-contribuer)
- [Environnement de développement](#environnement-de-développement)
- [Standards de code](#standards-de-code)
- [Processus de contribution](#processus-de-contribution)
- [Tests](#tests)
- [Documentation](#documentation)

## 🤝 Code de conduite

- Soyez respectueux et professionnel dans toutes les interactions
- Acceptez les critiques constructives
- Concentrez-vous sur ce qui est le mieux pour le projet
- Montrez de l'empathie envers les autres membres de la communauté

## 💡 Comment contribuer

### Types de contributions acceptées

- 🐛 **Corrections de bugs** : Rapports et fixes de bugs
- ✨ **Nouvelles fonctionnalités** : Propositions et implémentations (après discussion)
- 📚 **Documentation** : Améliorations de la documentation
- 🧪 **Tests** : Ajout de tests unitaires, fonctionnels ou E2E
- 🎨 **UI/UX** : Améliorations de l'interface utilisateur
- ⚡ **Performance** : Optimisations de performance

### Avant de commencer

1. Vérifiez que le problème n'est pas déjà signalé dans les Issues
2. Pour les nouvelles fonctionnalités, ouvrez une Issue pour discussion avant de commencer le développement
3. Assurez-vous que votre contribution est alignée avec la roadmap du projet

## 🛠️ Environnement de développement

### Prérequis

- **Docker** & **Docker Compose** (recommandé)
- **PHP 8.4+** (si développement local)
- **Composer 2.x**
- **Node.js 18+** & **npm** (pour assets)
- **Git**

### Installation

```bash
# Cloner le repository
git clone https://github.com/thibmonier/hotones.git
cd hotones

# Démarrer l'environnement Docker
docker compose up -d --build

# Installer les dépendances PHP
docker compose exec app composer install

# Créer la base de données et exécuter les migrations
docker compose exec app php bin/console doctrine:database:create
docker compose exec app php bin/console doctrine:migrations:migrate -n

# Charger les fixtures (données de test)
docker compose exec app php bin/console doctrine:fixtures:load -n

# Compiler les assets
./build-assets.sh dev
```

### Configuration

- L'application est accessible sur `http://localhost:8080`
- La base de données MariaDB est accessible sur `localhost:3307`
- Redis est disponible sur `localhost:6379`

## 📏 Standards de code

### Style de code

Nous suivons les standards **PSR-12** et **Symfony Coding Standards**.

```bash
# Vérifier le style de code
docker compose exec app composer phpstan

# Corriger automatiquement le style
docker compose exec app composer phpcsfixer-fix

# Vérifier la qualité du code
docker compose exec app composer check-code
```

### Conventions de nommage

#### PHP

- **Classes** : PascalCase (`ProjectController`, `ForecastingService`)
- **Méthodes** : camelCase (`createCampaign`, `calculateProgress`)
- **Variables** : camelCase (`$contributor`, `$yearlyStats`)
- **Constantes** : SCREAMING_SNAKE_CASE (`ROLE_MANAGER`, `STATUS_ACTIVE`)

#### Base de données

- **Tables** : snake_case pluriel (`performance_reviews`, `onboarding_tasks`)
- **Colonnes** : snake_case (`created_at`, `contributor_id`)

#### Routes

- **Noms** : snake_case (`performance_review_index`, `onboarding_team`)
- **URLs** : kebab-case (`/performance-reviews`, `/onboarding/team`)

### Architecture

#### Structure des contrôleurs

```php
#[Route('/resource')]
#[IsGranted('ROLE_REQUIRED')]
class ResourceController extends AbstractController
{
    public function __construct(
        private readonly ResourceService $service,
        private readonly ResourceRepository $repository,
    ) {
    }

    #[Route('', name: 'resource_index', methods: ['GET'])]
    public function index(): Response
    {
        // Logique minimale
        // Déléguer au service pour la logique métier
    }
}
```

#### Structure des services

```php
class ResourceService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ResourceRepository $repository,
    ) {
    }

    /**
     * Description claire de la méthode.
     *
     * @return ResourceType Description du retour
     */
    public function doSomething(Param $param): ResourceType
    {
        // Logique métier ici
    }
}
```

#### Entités Doctrine

```php
#[ORM\Entity(repositoryClass: ResourceRepository::class)]
#[ORM\Table(name: 'resources')]
#[ORM\HasLifecycleCallbacks]
class Resource
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new DateTimeImmutable();
    }
}
```

### Règles importantes

1. **Pas de logique métier dans les contrôleurs** - Utilisez les services
2. **Injection de dépendances** - Toujours via le constructeur
3. **Type hints stricts** - Utilisez `declare(strict_types=1);`
4. **Pas de Yoda conditions** - `if ($var === 'value')` pas `if ('value' === $var)`
5. **Sécurité CSRF** - Protégez tous les formulaires et actions sensibles
6. **Validation** - Toujours valider les entrées utilisateur
7. **Pas de code mort** - Supprimez le code inutilisé au lieu de le commenter

## 🔄 Processus de contribution

### Workflow Git

1. **Fork** le repository
2. **Créez une branche** depuis `main` :
   ```bash
   git checkout -b feat/ma-nouvelle-feature
   git checkout -b fix/mon-bug-fix
   ```
3. **Committez vos changements** :
   ```bash
   git commit -m "feat: Add new feature X"
   git commit -m "fix: Fix bug in Y"
   ```
4. **Poussez vers votre fork** :
   ```bash
   git push origin feat/ma-nouvelle-feature
   ```
5. **Ouvrez une Pull Request** vers `main`

### Conventions de commits

Utilisez le format **Conventional Commits** :

```
<type>(<scope>): <description>

[corps optionnel]

[footer optionnel]
```

**Types** :
- `feat:` Nouvelle fonctionnalité
- `fix:` Correction de bug
- `docs:` Documentation seulement
- `style:` Formatage, point-virgules manquants, etc.
- `refactor:` Refactoring de code
- `perf:` Amélioration de performance
- `test:` Ajout ou correction de tests
- `chore:` Maintenance (dépendances, config, etc.)

**Exemples** :
```
feat(sprint4): Add performance review workflow
fix(onboarding): Fix profile relationship ManyToMany
docs: Update CONTRIBUTING.md with code standards
test(services): Add unit tests for OnboardingService
```

### Pull Request

Votre PR doit :

1. ✅ **Passer tous les tests** automatiques
2. ✅ **Respecter les standards de code** (PHPStan, PHP CS Fixer)
3. ✅ **Inclure des tests** pour les nouvelles fonctionnalités
4. ✅ **Mettre à jour la documentation** si nécessaire
5. ✅ **Avoir une description claire** du problème résolu et de la solution
6. ✅ **Référencer les Issues** associées (`Closes #123`, `Fixes #456`)

**Template de PR** :

```markdown
## Description
[Description claire des changements]

## Type de changement
- [ ] Bug fix
- [ ] Nouvelle fonctionnalité
- [ ] Breaking change
- [ ] Documentation

## Checklist
- [ ] Tests ajoutés/modifiés
- [ ] Documentation mise à jour
- [ ] Code respecte les standards
- [ ] Commits suivent Conventional Commits
- [ ] PR liée à une Issue

## Tests effectués
[Description des tests manuels et automatiques]

## Screenshots (si applicable)
[Screenshots pour les changements UI]
```

## 🧪 Tests

### Exécuter les tests

```bash
# Tous les tests (sauf E2E)
docker compose exec app composer test

# Tests unitaires seulement
docker compose exec app composer test-unit

# Tests fonctionnels
docker compose exec app composer test-functional

# Tests d'intégration
docker compose exec app composer test-integration

# Tests API
docker compose exec app composer test-api

# Tests E2E (Panther)
docker compose exec app composer test-e2e
```

### Écrire des tests

#### Tests unitaires

```php
namespace App\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;

class MyServiceTest extends TestCase
{
    private MyService $service;

    protected function setUp(): void
    {
        $dependency = $this->createMock(DependencyInterface::class);
        $this->service = new MyService($dependency);
    }

    public function testSomething(): void
    {
        $result = $this->service->doSomething();

        $this->assertSame('expected', $result);
    }
}
```

#### Tests fonctionnels

```php
namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class MyControllerTest extends WebTestCase
{
    public function testPageLoads(): void
    {
        $client = static::createClient();
        $client->request('GET', '/my-route');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Expected Title');
    }
}
```

### Couverture de code

- **Objectif minimum** : 80% de couverture pour les services
- **Priorité** : Logique métier critique (calculs, workflows, permissions)
- **Facultatif** : Getters/setters simples, constructeurs

## 📚 Documentation

### Documentation à maintenir

Lors de l'ajout de nouvelles fonctionnalités, mettez à jour :

1. **README.md** - Si changements majeurs d'installation ou usage
2. **CLAUDE.md** - Commandes importantes, patterns architecturaux
3. **docs/** - Documentation technique détaillée
4. **Docblocks PHP** - Pour toutes les méthodes publiques des services

### Format de documentation

```php
/**
 * Description courte et claire de ce que fait la méthode.
 *
 * Description détaillée optionnelle avec contexte, exemples d'utilisation,
 * cas particuliers, etc.
 *
 * @param ParamType $param Description du paramètre
 * @param OtherType $other Description de l'autre paramètre
 *
 * @return ReturnType Description de ce qui est retourné
 *
 * @throws ExceptionType Description des conditions d'exception
 */
public function myMethod(ParamType $param, OtherType $other): ReturnType
{
    // ...
}
```

## ❓ Questions ?

Si vous avez des questions :

1. Consultez la [documentation](/docs)
2. Lisez [CLAUDE.md](CLAUDE.md) pour les guidelines du projet
3. Ouvrez une [Issue](https://github.com/thibmonier/hotones/issues) avec le label `question`

## 🙏 Merci !

Merci de prendre le temps de contribuer à HotOnes ! Chaque contribution, petite ou grande, est appréciée et aide à améliorer le projet.
