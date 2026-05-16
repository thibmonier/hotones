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
- **PHP 8.4+** avec extensions: bcmath, ctype, iconv, redis
- **Composer 2.x**
- **Node.js 18+** & **npm/yarn** (pour assets)
- **Git**
- **MariaDB 11.4** ou **MySQL 8.0+** (si développement local sans Docker)

### Installation

```bash
# Cloner le repository
git clone https://github.com/thibmonier/hotones.git
cd hotones

# Démarrer l'environnement Docker
docker compose up -d --build

# Installer les dépendances PHP
docker compose exec app composer install

# Exécuter les migrations
docker compose exec app php bin/console doctrine:migrations:migrate -n

# Charger les données de référence (profils métiers et technologies)
docker compose exec app php bin/console app:load-reference-data

# Créer des utilisateurs de test pour tous les rôles
docker compose exec app php bin/console app:create-test-users
# Créé: intervenant@test.com, chef-projet@test.com, manager@test.com,
#        compta@test.com, admin@test.com, superadmin@test.com
# Mot de passe pour tous: "password"

# (Optionnel) Générer des projets de test avec devis et temps passés
docker compose exec app php bin/console app:seed-projects-2025 --count=50

# Compiler les assets
./build-assets.sh dev
```

### Configuration

- **Application principale**: `http://localhost:8080`
- **Backoffice admin (EasyAdmin)**: `http://localhost:8080/backoffice` (ROLE_ADMIN requis)
- **Base de données MariaDB**: `localhost:3307` (user: symfony, password: symfony, db: hotones)
- **Redis**: `localhost:6379`
- **API Documentation**: `http://localhost:8080/api/documentation`

### Git hooks (pre-commit / pre-push)

```bash
git config --local core.hooksPath .githooks
```

Les hooks `.githooks/pre-commit` et `.githooks/pre-push` détectent automatiquement votre environnement (OPS-005) :

| Détection | Comportement |
|---|---|
| Docker daemon up + `docker compose` disponible | Exécute via le conteneur `app` (mode historique, identique à la CI) |
| Docker indisponible mais PHP + composer + `vendor/` présents | Fallback local : `composer phpcsfixer-fix` / `./vendor/bin/phpunit` |
| Aucune des deux disponibles | Skip avec un avertissement (le commit n'est pas bloqué silencieusement) |

Si vous voulez bypasser **intentionnellement** un hook : `git commit --no-verify` / `git push --no-verify`. Documentez la raison dans le message de commit.

#### Cas légitimes pour `--no-verify`

| Cas | OK avec `--no-verify` ? | Raison |
|---|:-:|---|
| Doc-only commit (pas de code modifié) | ✅ | Hook lourd inutile sur Markdown pur |
| Commit qui suit immédiatement un fix de hook (rebase / amend) | ✅ | Hook déjà passé sur version précédente |
| Pre-existing test failures sur main (non introduits par votre PR) | ✅ avec ADR | Documentez via ADR + skip-pre-push marker |
| Vous voulez juste pousser plus vite | ❌ | Le hook a une raison d'être |
| Vous savez que le hook va échouer sur votre code | ❌ | Fixez le code, pas bypassez la vérif |
| CI / production push | ❌ jamais | La CI doit toujours vérifier |

#### Pre-flight check avant push

Avant un push, exécutez localement la même validation que le hook :

```bash
# Tests unitaires (rapides, ~1s)
docker compose exec app vendor/bin/phpunit --testsuite=unit

# Suite complète (peut révéler des pre-existing failures)
docker compose exec app vendor/bin/phpunit
```

Si la suite complète a des failures **pre-existing** (sur main, non introduits par votre branche) :
1. Ouvrez une issue documentant les failures (cf. `INVESTIGATE-FUNCTIONAL-FAILURES` sprint-008)
2. Push avec `--no-verify` est acceptable, mais référencez l'issue dans le message de commit

### Pre-commit fichiers auto-générés (OPS-014)

Le hook `pre-commit` rejette systématiquement les fichiers auto-générés stagés.
Sprint-005 a vu `config/reference.php` polluer les diffs avant qu'OPS-012 le
gitignore ; ce hook agit comme garde-fou en amont.

Patterns refusés :

| Pattern | Régénéré par |
|---|---|
| `config/reference.php` | Symfony Maker (introspection config) |
| `var/cache/**` | Symfony cache:warmup |
| `var/log/**` | Symfony logs runtime |
| `.phpunit.cache` | PHPUnit 13 result cache |
| `.deptrac.cache` | Deptrac analyze |
| `.php-cs-fixer.cache` | CS-Fixer cache |

Si le hook se déclenche :

1. **Tracké à tort** (ajouté avant le `.gitignore`) :
   ```bash
   git rm --cached <fichier>
   git commit -m "chore: untrack auto-generated <fichier>"
   ```
2. **Modification volontaire** (rare) : `git commit --no-verify` avec justification dans le message.

Le check tourne **avant** php-cs-fixer pour fail-fast. Implémentation :
`.githooks/pre-commit-autogenfiles.sh`.

### Pre-push baseline (OPS-011)

Le pre-push hook lance la suite **sans** les tests marqués `#[Group('skip-pre-push')]`. CI lance la suite **complète**. Cette dichotomie permet :

- Au développeur de pusher rapidement sans heurter la baseline historique de tests fragiles (multi-tenant filters, session brittleness, repository inverse-side sync).
- À la CI de continuer à signaler ces failures pour qu'elles soient adressées.

#### Liste des classes skippées

> **Audit sprint-006** (TEST-FUNCTIONAL-FIXES-002, PRs #100 → #103) :
> 5 markers retirés (causes racines fixées), 3 markers conservés via ADR-0003,
> 6 markers restent à auditer hors scope sprint-006.

**Markers conservés (legacy tolérée — voir [ADR-0003](docs/02-architecture/adr/0003-test-legacy-tolerance-vacation-csrf-session-boundary.md))**

| Test | Catégorie | Raison |
|---|---|---|
| `Functional\Vacation\CancelNotificationFlowTest` | CSRF + Messenger | `SessionNotFoundException` sur CSRF token via container test (Symfony 7+/8+ isolation request/container) |
| `Functional\Controller\Vacation\VacationApprovalControllerTest` | CSRF/Session | Idem |
| `Functional\Controller\Vacation\VacationRequestControllerTest` | CSRF/Session | Idem |

Refonte planifiée **EPIC-001 phase 2** (migration BC Vacation → DDD complet) ou refactor centralisé `SessionAwareTestTrait`.

**Markers à auditer (hors scope sprint-006, candidats sprint-009+)**

| Test | Catégorie | Raison |
|---|---|---|
| `MultiTenant\ControllerAccessControlTest` | Multi-tenant | Filtre company-context fait fail certains asserts |
| `Controller\Analytics\DashboardControllerTest` | Session | Period selection state perdu entre requests |
| `Controller\HomeControllerTest` | Auth | Auth flow flaky en test |
| `Service\NotificationEventChainTest` | Integration | Event dispatch non-déterministe en test container |
| `Controller\Admin\OnboardingTemplateControllerTest` | Admin | Patterns admin EA5 non couverts par fixtures (cf ADR-0004 sprint-008) |
| `Controller\TimesheetControllerTest` | Multi-tenant | Filter exclut les fixtures cross-company |
| `MultiTenant\TenantFilterRegressionTest` | Multi-tenant | **Régression sprint-007** (3/4 tests) — `find()` ne semble pas appliquer le SQLFilter. Story `SEC-MULTITENANT-FIX-001` sprint-009 (2 pts). cf ADR-0004 |

**Markers retirés (causes racines fixées sprint-006)**

| Test | Story | Cause racine |
|---|---|---|
| `Controller\OnboardingControllerTest` | T-TFF2-02 #101 | Bloqué par `MultiTenantTestTrait` UNIQUE collisions (fix T-TFF2-01) |
| `Controller\PerformanceReviewControllerTest` | T-TFF2-02 #101 | Idem |
| `Controller\OrderControllerPreviewTest` | T-TFF2-03 #102 | Idem (passait déjà après fix T-TFF2-01) |
| `Controller\ProjectControllerFilterTest` | T-TFF2-03 #102 | Deprecation PHP 8.4 sur `null` array offset (`ProjectRepository:376`) |
| `Repository\RunningTimerRepositoryTest` | T-TFF2-03 #102 | API drift Foundry v2 — `->_real()` obsolète |

#### Comment ajouter un test

```php
use PHPUnit\Framework\Attributes\Group;

// {Justification métier ou technique — référence ADR si applicable}
// Ex: voir docs/02-architecture/adr/NNNN-...md
#[Group('skip-pre-push')]
final class MyBrittleTest extends WebTestCase { /* ... */ }
```

**Règle de review** : tout nouveau marker `#[Group('skip-pre-push')]` doit être accompagné :
1. D'un commentaire au-dessus indiquant la cause racine + la story de fix prévue OU une référence ADR existante.
2. D'une PR de fix planifiée dans le sprint courant ou sprint suivant.
3. Sinon **refus en review**.

#### Comment retirer un test

**Avant de retirer** :

1. Vérifier si le test fait partie de la liste **markers conservés** ci-dessus → consulter l'ADR référencé. Ne pas retirer à l'aveugle.
2. Sinon : identifier la cause racine du fail (ex: bug production révélé par le test, ou bug d'infrastructure de test).

**Procédure** :

1. **Fixer la cause racine** (côté production OU côté infrastructure de test). Documenter en commit.
2. Supprimer le marker `#[Group('skip-pre-push')]` (et l'import `Group` si devenu inutilisé).
3. Vérifier que le test passe en local : `vendor/bin/phpunit chemin/vers/MyTest.php`.
4. Vérifier que le pre-push complet passe : `make pre-push` (ou équivalent) sans `--no-verify`.
5. Mettre à jour la table **Markers retirés** ci-dessus avec story + PR + cause racine.

**Bonus T-TFF2-01** : si plusieurs markers tombent simultanément après une seule correction (ex: fix `MultiTenantTestTrait` débloque ~3 tests), c'est probablement le bon signal — un seul fix architectural peut résoudre plusieurs tests à la fois. Privilégier ce type de refactor centralisé aux fixes individuels.

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

**Contrôleurs standards (application)** :
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

**Contrôleurs CRUD EasyAdmin (backoffice)** :
```php
namespace App\Controller\Admin;

use App\Entity\Resource;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use Override;

class ResourceCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Resource::class;
    }

    #[Override]
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ressource')
            ->setEntityLabelInPlural('Ressources')
            ->setSearchFields(['name', 'slug'])
            ->setDefaultSort(['name' => 'ASC']);
    }

    #[Override]
    public function configureFields(string $pageName): iterable
    {
        // Configuration des champs du CRUD
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom')->setRequired(true);
        // ...
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
3. **Type hints stricts** - Utilisez `declare(strict_types=1);` en tête de fichier
4. **Pas de Yoda conditions** - `if ($var === 'value')` pas `if ('value' === $var)`
5. **Sécurité CSRF** - Protégez tous les formulaires et actions sensibles
6. **Validation** - Toujours valider les entrées utilisateur
7. **Pas de code mort** - Supprimez le code inutilisé au lieu de le commenter
8. **Multi-tenancy** - Toutes les entités métier doivent avoir une relation `ManyToOne` vers `Company`
9. **Isolation des données** - Toujours filtrer par Company dans les repositories
10. **Permissions EasyAdmin** - Utiliser `setPermission()` pour restreindre l'accès aux actions CRUD

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
   - ⚠️ Vérifier le quota : max **4 PRs en review active** par développeur
     (voir [PRs ouvertes simultanées](#prs-ouvertes-simultanées--quota-par-développeur)).
     Au-delà → ouvrir en draft.

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
7. ✅ **Respecter la taille maximale** : voir section ci-dessous
8. ✅ **Si workflow gated** : section "Workflow gated" du `PULL_REQUEST_TEMPLATE.md` cochée — secrets/vars provisionnés AVANT merge (politique OPS-015).

### Taille de Pull Request — politique <400 lignes

Pour garder la review humaine efficace et accélérer les merges, **une PR ne doit pas dépasser 400 lignes diff cumulées** (additions + suppressions).

Les fichiers suivants sont **exclus** du calcul :

- Fichiers générés automatiquement : `composer.lock`, `package-lock.json`, `yarn.lock`, `phpstan-baseline.neon`
- Migrations Doctrine auto-générées (`migrations/Version*.php` produits par `doctrine:migrations:diff`)
- Snapshots de tests (`tests/__snapshots__/**`, `*.snap`)
- Assets compilés (`public/build/**`, `public/assets/**`)
- Fichiers de traduction auto-extraits (`translations/*.xlf` regénérés)

**Si votre PR dépasse 400 lignes :**

- Découpez-la en commits atomiques **clairement nommés** (chacun reviewable indépendamment) ; un reviewer doit pouvoir naviguer commit par commit via `gh pr diff --commit <sha>`
- Ou découpez-la en **PRs stack** (chaque PR cible la précédente, exemple `feat/foo-base` → `feat/foo-extension`) ; voir le pattern utilisé sur sprint-002 (#32 → #39 → #40 → #43)
- Ou justifiez explicitement la taille dans la description : reasons acceptées sont migration legacy massive, refacto sécurité OWASP, dépendance technique forçant un changement large

**Le reviewer peut demander un découpage avant de commencer la revue** si la règle est cassée sans justification. Une PR > 800 lignes sans split sera systématiquement renvoyée.

#### Mesurer la taille avant push

```bash
# Lignes diff cumulées sur le HEAD courant vs main
git diff main...HEAD --shortstat

# Avec exclusions (composer.lock, migrations, snapshots)
git diff main...HEAD --shortstat -- ':(exclude)composer.lock' ':(exclude)package-lock.json' ':(exclude)migrations/Version*.php'
```

#### Référence

- Origine : retro sprint-002, action 5 (OPS-006).
- ADR : à venir si l'équipe décide d'un split policy plus formelle.

### PR empilées (stacked PRs) — procédure

Quand un travail dépasse 400 lignes ou se décompose naturellement en
couches (ADR → infra → feature), on empile plusieurs PRs où chacune cible
la précédente plutôt que `main`. Cela accélère la review (chaque PR est
petite) sans bloquer le travail dépendant.

#### Quand empiler

- ✅ Refactor en plusieurs étapes : extraction interface → migration
  consommateurs → suppression code legacy.
- ✅ Feature dont la base technique mérite review séparée
  (ex : nouveau Doctrine type → entity → service → UI).
- ✅ ADR + son implémentation (ADR mergé d'abord, code ensuite).
- ❌ Fix simple (< 100 lignes) : reste en PR unique vers `main`.
- ❌ PRs sans dépendance forte : merger en parallèle, pas en stack.

#### Convention de nommage

```
feat/<story>-base       (ouvre PR vers main)
feat/<story>-step1      (ouvre PR vers feat/<story>-base)
feat/<story>-step2      (ouvre PR vers feat/<story>-step1)
```

Documentez la chaîne dans chaque description : *« Stack: PR #X → PR #Y → PR #Z »*.

#### Workflow de création

```bash
# 1. Base
git checkout main && git pull
git checkout -b feat/foo-base
# ... code base ...
git push -u origin feat/foo-base
gh pr create --base main --title "feat(foo): base" --body "..."

# 2. Étape 2 (cible la base, pas main)
git checkout -b feat/foo-step1
# ... code step 1 ...
git push -u origin feat/foo-step1
gh pr create --base feat/foo-base --title "feat(foo): step 1" --body "..."
```

Le helper `bin/stacked-pr` automatise le scaffolding (voir
`docs/04-development/stacked-prs.md`).

#### Workflow de merge (ordre strict)

1. Reviewer + merge `feat/foo-base` vers `main` (squash recommandé).
2. **Rebaser la PR suivante** sur `main` :
   ```bash
   git checkout feat/foo-step1
   git fetch origin
   git rebase origin/main
   git push --force-with-lease
   ```
3. Mettre à jour la base de la PR sur GitHub :
   ```bash
   gh pr edit <pr-step1> --base main
   ```
4. Reviewer + merge.
5. Répéter pour chaque étape suivante.

GitHub fait parfois ce changement de base automatiquement après le merge
de la PR parent ; vérifiez tout de même via `gh pr view` que la cible est
bien `main` avant de mergrer.

#### Erreurs fréquentes

| Symptôme | Cause | Fix |
|---|---|---|
| PR step1 affiche les commits de la base + ses propres commits | Pas rebasé après merge de la base | `git rebase origin/main` puis `gh pr edit --base main` |
| Conflits sur composer.lock à chaque rebase | Lock divergé entre étapes | Régénérer le lock une fois sur la base, le partager dans la step |
| Reviewer dit "je ne vois que la base" | Mauvaise base GitHub | `gh pr edit <num> --base feat/foo-base` |
| Force-push refusé | Branch protection sur la base | Demander à un mainteneur ou attendre le merge de la base |

**Référence** : sprint-002 stack #32 → #39 → #40 → #43, sprint-003 stack
#50 → #54, #56 → #57, #66 → #67. Action retro sprint-003 #1.

### PRs ouvertes simultanées — quota par développeur

Pour garder la file de review humaine viable, **chaque développeur ne doit
pas avoir plus de 4 PRs en review active simultanément**.

Au-delà :

- Mettre les PRs supplémentaires en **draft** (`gh pr create --draft` ou
  `gh pr ready --undo` sur une PR existante).
- Sortir du draft uniquement quand l'une des PRs en review est mergée.

**Pourquoi 4** :

- 4 = nombre raisonnable pour un reviewer humain à garder en tête sans
  perdre le contexte.
- Sprint-004 a tenté 10 PRs en parallèle : file de review ingérable,
  certaines PRs ont attendu plusieurs jours faute de bande passante.

**Exception : stack PR**. Les PRs d'une même chaîne (cf section ci-dessus)
comptent pour **1 seule** dans le quota — le reviewer descend la stack en
une session, donc le coût cognitif est partagé.

**Vérifier son quota courant** :

```bash
gh pr list --author=@me --state=open --json number,title,isDraft \
  --jq '.[] | select(.isDraft|not) | "#\(.number) \(.title)"'
```

**Référence** : retro sprint-004 action #4. Story OPS-013 (sprint-005).

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

#### `createMock` vs `createStub` — guide de décision

> **Origine**: TEST-MOCKS-002/003 (sprints 006-007). PHPUnit 13 émet une PHPUnit Notice "No expectations were configured" pour chaque `createMock(...)` qui n'utilise jamais `->expects(...)`. Solution: utiliser `createStub` quand on n'a pas besoin de vérifier les appels.

```
┌─────────────────────────────────────────────────────────┐
│ Le mock va-t-il utiliser ->expects(...) ou ->with(...) ?│
├─────────────────────────────────────────────────────────┤
│   OUI → createMock (vérification d'appel)               │
│   NON → createStub (juste un faux retour de méthode)    │
└─────────────────────────────────────────────────────────┘
```

##### Règles concrètes

| Cas | Outil | Raison |
|---|---|---|
| `$mock->expects($this->once())->method('do')` | `createMock` | Vérification du nombre d'appels |
| `$mock->expects($this->once())->method('do')->with($arg)` | `createMock` | Vérification arguments |
| `$mock->method('find')->willReturn($entity)` | `createStub` | Juste configurer un retour |
| `$mock->method('find')->willReturnCallback(fn ...)` | `createStub` | Pas de vérification d'appel |
| Mock passé en argument et jamais asserted | `createStub` | Inline arg-passed mock |
| Type declaration: `private TYPE&MockObject $foo` | `createMock` ou changer → `&Stub` | Sinon TypeError sur assignation |

##### Audit script

Pour identifier rapidement les conversions safe sur un fichier (ou batch):

```bash
# Audit un fichier
perl tools/count-mocks.pl tests/Unit/Service/FooTest.php

# Audit toute la suite Unit (JSON pour scripting)
perl tools/count-mocks.pl --json $(find tests/Unit -name '*Test.php')
```

Le script identifie chaque mock et indique si la conversion est `CONVERTIBLE` ou nécessite une `REVIEW` (présence d'`expects`/`with` ou type declaration plain `MockObject`).

##### Type declarations

```php
// ❌ MauvAis: après conversion à createStub, TypeError sur assignation
private MyClass&MockObject $foo;
$this->foo = $this->createStub(MyClass::class);

// ✅ Bon: type aligné
private MyClass&Stub $foo;
$this->foo = $this->createStub(MyClass::class);

// ✅ Acceptable: pas de type d'intersection
private MyClass $foo;
$this->foo = $this->createStub(MyClass::class);
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

## 🗄️ Données de test

### Commandes de génération

Le projet inclut plusieurs commandes pour générer des données de test :

```bash
# Charger les données de référence (profils métiers, technologies)
docker compose exec app php bin/console app:load-reference-data [--company-id=X]

# Créer des utilisateurs de test pour tous les rôles
docker compose exec app php bin/console app:create-test-users [--company-id=X]
# Créé 6 utilisateurs: intervenant, chef-projet, manager, compta, admin, superadmin
# Mot de passe: "password"

# Générer des projets de test complets (devis + tâches + temps passés)
docker compose exec app php bin/console app:seed-projects-2025 \
  --count=50 \
  --year=2025 \
  [--company-id=X]
# Génère: projets, devis signés, tâches, temps passés sur toute l'année

# Recalculer les métriques analytics
docker compose exec app php bin/console app:metrics:dispatch --year=2025
```

### Structure des données générées

- **Profils métiers** : 15 profils (fullstack, frontend, backend, lead dev, chef de projet, etc.)
- **Technologies** : 20 technologies avec couleurs (Symfony, React, Vue, Angular, etc.)
- **Contributeurs** : 7 contributeurs avec profils et CJM variables
- **Projets** : Projets forfait/régie avec statut actif/complété
- **Devis** : Sections + lignes de service avec jours/TJM + achats
- **Tâches** : 3-6 tâches par projet avec estimations
- **Timesheets** : Temps passés répartis sur l'année (jours ouvrés, 25% de remplissage)

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

## 📦 Cadence updates dépendances

> **US-089 (sprint-014)** — Routine de mise à jour des dépendances Composer + npm.

### Dependabot (automatique)

Le projet utilise [Dependabot](.github/dependabot.yml) pour ouvrir des PRs
automatiques de mise à jour :

| Écosystème | Cadence | Limite PRs ouvertes |
|---|---|---:|
| Composer (PHP) | Hebdomadaire (lundi 06:00 Europe/Paris) | 5 |
| npm (JS) | Hebdomadaire (lundi 06:00 Europe/Paris) | 5 |
| GitHub Actions | Mensuelle (1er lundi 06:00) | 3 |
| Docker | Mensuelle (1er lundi 06:00) | 3 |

Les PRs sont **groupées** par famille (Symfony, Doctrine, Webpack) pour
les versions minor + patch afin de réduire le bruit.

### Convention manuelle (entre 2 cycles Dependabot)

Pour rafraîchir manuellement les dépendances :

```bash
# 1. Composer (patch + minor uniquement, jamais major sans ADR)
docker exec hotones_app composer update --with-all-dependencies "symfony/*" "doctrine/*"

# 2. npm
docker exec hotones_app npm update --no-audit

# 3. composer bump (mettre à jour les contraintes vers les versions installées)
docker exec hotones_app composer bump

# 4. Validation
docker exec hotones_app php bin/phpunit --no-coverage
docker exec hotones_app composer phpstan
docker exec hotones_app composer phpcsfixer
```

### Rebase Dependabot PRs

Avant merge, **toujours rebaser** la PR Dependabot sur main :

```bash
gh pr checkout <numéro>
git rebase origin/main
git push --force-with-lease
```

### Politique de merge

- **Patch (x.y.Z)** : merge auto si CI verte
- **Minor (x.Y.z)** : revue rapide + merge si CI verte + tests Unit
- **Major (X.y.z)** : ADR obligatoire (breaking changes) + sprint dédié

### Audit sécurité ponctuel

```bash
docker exec hotones_app composer audit  # PHP
docker exec hotones_app npm audit       # JS
```

Cf. `.snyk` policy pour la stratégie Snyk (US-088).

---

## 🔧 Mago lint — cleanup progressif

Mago est l'analyseur principal qualité code (config `mago.toml`).
Baseline résiduel : `mago-baseline.json` (1431 issues — cleanup batch initial
sprint-025 MAGO-LINT-BATCH-001 + batch résiduel sprint-026 MAGO-LINT-BATCH-002).

### Décomposition baseline résiduelle (sprint-026)

| Catégorie | Count | Stratégie |
|---|---:|---|
| `assertion-style` (`self::assert*` → `static::`) | 113 | Mass fix `--unsafe` — déféré sprint-027 (approval explicit requise) |
| `excessive-parameter-list` (DTOs KPI multi-champs) | 5 | Légitime — DTOs read-only avec 7-9 champs propres |
| `too-many-methods` (test classes 13-14 méthodes) | 2 | Légitime — coverage scénarios calculator |
| `single-class-per-file` (Spy co-localisée tests Integration) | 2 | Pattern volontaire — Spy simple proche de son consumer |
| `no-isset` (pattern array aggregation `if (!isset($x[$k]))`) | 2 | Idiomatique — values jamais null par construction |
| **Total résiduel sprint-026** | **128** | Absorbé baseline, plan reprise sprint-027 |
| Baseline antérieure sprint-025 | 1303 | Cleanup en cours par lots progressifs |

### Workflow contributeur

```bash
# Vérifier la conformité (la baseline filtre les issues existantes)
make mago

# Détail des issues filtrées (audit local — ne pas commit)
docker compose exec app vendor/bin/mago lint  # sans --baseline override

# Auto-fix safe sur fichiers ou dossier modifiés
docker compose exec app vendor/bin/mago lint --fix <path>

# Auto-fix incluant les changements potentiellement risqués
# (vérifier le diff + tests verts avant commit)
docker compose exec app vendor/bin/mago lint --fix --potentially-unsafe <path>
```

### Stratégie cleanup résiduel

1. **Ne pas régénérer** la baseline globalement — chaque PR doit **réduire**
   les issues, pas les masquer.
2. Sur un fichier touché par un PR : run `mago lint --fix <fichier>` et commit
   les fixes safe. Les remaining sur ce fichier sortent automatiquement du
   baseline (le baseline ne match que les issues identiques au moment de
   sa génération).
3. Sprints futurs : poursuite du cleanup par lots ciblés (cf retro
   sprint-025).

### Procédure rebase stack PR adjacent

Quand plusieurs PRs touchent les mêmes fichiers, merger **stack par stack
complet** AVANT de rebaser le stack adjacent sur main MAJ (cf sprint-024
retro A-3). Sinon : conflits silencieux (services.yaml, Controller, template,
test) qui passent CI mais cassent la suite complète sur main.

```bash
# Stack US-X : 3 PRs interdépendantes
gh pr merge X1 X2 X3 --squash --delete-branch  # tout le stack

# Stack US-Y branché sur version pré-X
git checkout feat/us-y && git rebase origin/main  # MAJ post-stack-X
gh pr merge Y1 Y2 Y3 --squash --delete-branch
```

## ❓ Questions ?

Si vous avez des questions :

1. Consultez la [documentation](/docs)
2. Lisez [CLAUDE.md](CLAUDE.md) pour les guidelines du projet
3. Ouvrez une [Issue](https://github.com/thibmonier/hotones/issues) avec le label `question`

## 🙏 Merci !

Merci de prendre le temps de contribuer à HotOnes ! Chaque contribution, petite ou grande, est appréciée et aide à améliorer le projet.
