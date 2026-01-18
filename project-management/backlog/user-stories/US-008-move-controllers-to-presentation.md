# US-008: Déplacer les Controllers vers la couche Presentation

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** 📋 Backlog

---

## Description

**En tant que** développeur
**Je veux** déplacer tous les Controllers vers la couche Presentation avec une organisation claire (Web/Api/Admin)
**Afin de** respecter Clean Architecture et séparer la présentation de la logique métier

---

## Critères d'acceptation

### GIVEN: Les controllers existent dans src/Controller/

**WHEN:** Je déplace les controllers vers Presentation

**THEN:**
- [ ] Controllers Web dans `src/Presentation/Controller/Web/`
- [ ] Controllers Api dans `src/Presentation/Controller/Api/`
- [ ] Controllers Admin dans `src/Presentation/Controller/Admin/`
- [ ] Namespaces mis à jour (`App\Presentation\Controller\Web`, etc.)
- [ ] Aucune annotation Doctrine dans les controllers
- [ ] Controllers délèguent aux Use Cases (pas de logique métier directe)
- [ ] Aucune manipulation directe de `EntityManagerInterface`

### GIVEN: Les controllers sont déplacés

**WHEN:** J'exécute `make console CMD="debug:router"`

**THEN:**
- [ ] Toutes les routes sont reconnues
- [ ] Pas d'erreur "Controller not found"
- [ ] Namespaces des controllers corrects dans la sortie
- [ ] Routes organisées par préfixe (/, /api/, /admin/)

### GIVEN: Les controllers sont déplacés

**WHEN:** J'exécute les tests fonctionnels

**THEN:**
- [ ] `make test-functional` passe sans erreur
- [ ] Tests WebTestCase fonctionnent
- [ ] Pas de régression sur les routes testées
- [ ] Autoload Composer fonctionne

---

## Tâches techniques

### [ANALYSE] Analyser les controllers existants (1h)

**Actions:**
- Lister tous les fichiers dans `src/Controller/`
- Identifier le type de chaque controller:
  - **Web**: Controllers retournant HTML (Twig templates)
  - **Api**: Controllers API REST (JSON responses)
  - **Admin**: Controllers EasyAdmin ou admin custom
- Analyser les dépendances injectées
- Vérifier les routes déclarées (annotations ou YAML)

**Commande:**
```bash
make console CMD="debug:router" > var/routes-before.txt
find src/Controller -name "*.php" -type f | xargs grep -l "class.*Controller"
```

### [REFACTOR] Déplacer Web Controllers (1.5h)

**Avant:**
```
src/Controller/
├── HomeController.php
├── ReservationController.php
├── SejourController.php
└── ContactController.php
```

**Après:**
```
src/Presentation/Controller/Web/
├── HomeController.php
├── ReservationController.php
├── SejourController.php
└── ContactController.php
```

**Namespace avant:**
```php
<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReservationController extends AbstractController
{
    #[Route('/reservations', name: 'reservation_list')]
    public function list(): Response
    {
        // ...
    }
}
```

**Namespace après:**
```php
<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    #[Route('/reservations', name: 'reservation_list')]
    public function list(): Response
    {
        // ...
    }
}
```

**Actions:**
- Déplacer fichiers vers `src/Presentation/Controller/Web/`
- Mettre à jour namespace dans chaque fichier
- Ajouter `declare(strict_types=1)` si manquant
- Marquer classe `final` si possible
- Utiliser `Route` attribute (Symfony 6.4+) au lieu de annotation

### [REFACTOR] Déplacer Api Controllers (1h)

**Avant:**
```
src/Controller/
├── ApiController.php
└── Api/
    ├── ReservationApiController.php
    └── SejourApiController.php
```

**Après:**
```
src/Presentation/Controller/Api/
├── ReservationApiController.php
└── SejourApiController.php
```

**Exemple Api Controller:**
```php
<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api', name: 'api_')]
final class ReservationApiController extends AbstractController
{
    public function __construct(
        private readonly GetReservationDetailsQueryHandler $queryHandler,
    ) {}

    #[Route('/reservations/{id}', name: 'reservation_show', methods: ['GET'])]
    public function show(string $id): JsonResponse
    {
        $query = new GetReservationDetailsQuery($id);
        $dto = $this->queryHandler->handle($query);

        return $this->json($dto);
    }
}
```

### [REFACTOR] Déplacer Admin Controllers (1h)

**Avant:**
```
src/Controller/Admin/
├── DashboardController.php
├── ReservationCrudController.php
└── SejourCrudController.php
```

**Après:**
```
src/Presentation/Controller/Admin/
├── DashboardController.php
├── ReservationCrudController.php
└── SejourCrudController.php
```

**Exemple EasyAdmin Controller:**
```php
<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DashboardController extends AbstractDashboardController
{
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        return parent::index();
    }
}
```

### [CONFIG] Mettre à jour routes (0.5h)

**Si routes en annotations/attributes:**
```php
// ✅ Pas de modification nécessaire (auto-découverte PSR-4)
#[Route('/reservations', name: 'reservation_list')]
```

**Si routes en YAML (config/routes.yaml):**

**Avant:**
```yaml
# config/routes.yaml
controllers:
    resource: ../src/Controller/
    type: attribute
```

**Après:**
```yaml
# config/routes.yaml
presentation_web:
    resource: ../src/Presentation/Controller/Web/
    type: attribute

presentation_api:
    resource: ../src/Presentation/Controller/Api/
    type: attribute
    prefix: /api

presentation_admin:
    resource: ../src/Presentation/Controller/Admin/
    type: attribute
    prefix: /admin
```

**Vérification:**
```bash
make console CMD="debug:router" > var/routes-after.txt
diff var/routes-before.txt var/routes-after.txt
```

### [TEST] Mettre à jour les tests fonctionnels (1.5h)

**Tests concernés:**
```
tests/Functional/Controller/
├── ReservationControllerTest.php
├── SejourControllerTest.php
└── Admin/
    └── DashboardControllerTest.php
```

**Avant (import):**
```php
<?php

namespace App\Tests\Functional\Controller;

use App\Controller\ReservationController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationControllerTest extends WebTestCase
{
    // ...
}
```

**Après (import):**
```php
<?php

namespace App\Tests\Functional\Controller;

use App\Presentation\Controller\Web\ReservationController;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function it_displays_reservation_list(): void
    {
        $client = static::createClient();

        // When
        $client->request('GET', '/reservations');

        // Then
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Réservations');
    }
}
```

**Actions:**
- Mettre à jour les imports dans les tests
- Vérifier que les routes utilisées dans `$client->request()` sont correctes
- Exécuter `make test-functional` pour valider

### [DOC] Documenter la nouvelle structure (0.5h)

**Mettre à jour README.md:**

```markdown
## Structure Presentation Layer

Les controllers sont organisés par type :

### Web Controllers (`src/Presentation/Controller/Web/`)
Controllers retournant du HTML (Twig templates) :
- `HomeController` - Page d'accueil
- `ReservationController` - Gestion réservations client
- `SejourController` - Catalogue séjours
- `ContactController` - Formulaire contact

### Api Controllers (`src/Presentation/Controller/Api/`)
Controllers API REST (JSON) :
- `ReservationApiController` - API réservations
- `SejourApiController` - API séjours
- Prefix routes: `/api/`

### Admin Controllers (`src/Presentation/Controller/Admin/`)
Controllers administration (EasyAdmin) :
- `DashboardController` - Dashboard admin
- `ReservationCrudController` - CRUD réservations
- `SejourCrudController` - CRUD séjours
- Prefix routes: `/admin/`

### Organisation par type

```
src/Presentation/Controller/
├── Web/           # HTML/Twig (utilisateurs finaux)
├── Api/           # JSON REST (applications externes)
└── Admin/         # Interface administration (staff)
```
```

**Créer exemple:**
```bash
# .claude/examples/controller-organization.md
```

### [VALIDATION] Valider la migration (0.5h)

**Checklist:**
```bash
# 1. Autoload Composer
make composer CMD="dump-autoload"

# 2. Vérifier routes
make console CMD="debug:router"
# Toutes les routes doivent être listées

# 3. PHPStan
make phpstan
# Aucune erreur de namespace

# 4. Tests fonctionnels
make test-functional
# Tous les tests passent

# 5. Accès manuel
# Ouvrir http://localhost:8080 et tester les pages
```

---

## Définition de Done (DoD)

- [ ] Tous les controllers déplacés vers `src/Presentation/Controller/`
- [ ] Controllers Web dans sous-répertoire `Web/`
- [ ] Controllers Api dans sous-répertoire `Api/`
- [ ] Controllers Admin dans sous-répertoire `Admin/`
- [ ] Namespaces mis à jour (`App\Presentation\Controller\Web`, etc.)
- [ ] `declare(strict_types=1)` ajouté dans chaque fichier
- [ ] Classes marquées `final` quand possible
- [ ] `composer dump-autoload` exécuté sans erreur
- [ ] `make console CMD="debug:router"` liste toutes les routes
- [ ] Routes accessibles (pas d'erreur 404)
- [ ] Tests fonctionnels passent (`make test-functional`)
- [ ] Imports mis à jour dans les tests
- [ ] Aucun controller ne contient de logique métier (délégation Use Cases)
- [ ] Aucune manipulation directe de `EntityManagerInterface`
- [ ] README.md mis à jour avec nouvelle structure
- [ ] PHPStan niveau max passe
- [ ] Code review effectué par Tech Lead
- [ ] Commit avec message: `refactor(presentation): move controllers to Presentation layer with Web/Api/Admin organization`

---

## Notes techniques

### Catégorisation des Controllers

#### Web Controllers (HTML/Twig)
**Critères:**
- Retournent `Response` avec `render()`
- Utilisent Twig templates
- Destinés aux utilisateurs finaux (navigateur)
- Gèrent les formulaires Symfony

**Exemples:**
- `HomeController` - Page d'accueil
- `ReservationController` - Liste/détails réservations
- `SejourController` - Catalogue séjours
- `ContactController` - Formulaire contact

#### Api Controllers (JSON)
**Critères:**
- Retournent `JsonResponse`
- Pas de templates Twig
- Destinés aux applications externes
- Routes préfixées `/api/`

**Exemples:**
- `ReservationApiController` - CRUD réservations JSON
- `SejourApiController` - Catalogue séjours JSON

#### Admin Controllers (EasyAdmin)
**Critères:**
- Étendent `AbstractDashboardController` ou `AbstractCrudController`
- Interface administration
- Routes préfixées `/admin/`
- Réservé au staff

**Exemples:**
- `DashboardController` - Dashboard EasyAdmin
- `ReservationCrudController` - CRUD admin
- `SejourCrudController` - CRUD admin

### Routes Configuration

#### Annotations/Attributes (Recommandé)

```php
<?php

namespace App\Presentation\Controller\Web;

use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    // ✅ Route définie via attribute (auto-découverte PSR-4)
    #[Route('/reservations', name: 'reservation_list', methods: ['GET'])]
    public function list(): Response
    {
        // ...
    }
}
```

**Avantages:**
- Auto-découverte via PSR-4
- Pas de configuration YAML nécessaire
- Routes co-localisées avec le controller

#### Configuration YAML (Alternative)

```yaml
# config/routes.yaml

# Controllers Web
presentation_web:
    resource: ../src/Presentation/Controller/Web/
    type: attribute

# Controllers Api (avec prefix)
presentation_api:
    resource: ../src/Presentation/Controller/Api/
    type: attribute
    prefix: /api
    name_prefix: api_

# Controllers Admin (avec prefix)
presentation_admin:
    resource: ../src/Presentation/Controller/Admin/
    type: attribute
    prefix: /admin
    name_prefix: admin_
```

### Autowiring Symfony

Symfony 6.4 auto-wire automatiquement les controllers via PSR-4. **Aucune configuration services.yaml nécessaire** tant que:
- ✅ Namespace commence par `App\`
- ✅ Classe dans `src/`
- ✅ PSR-4 respecté

```yaml
# config/services.yaml
services:
    # ✅ Auto-configuration pour tous les controllers
    _defaults:
        autowire: true
        autoconfigure: true

    App\:
        resource: '../src/'
        exclude:
            - '../src/Domain/'    # Domain pur (pas de Symfony)
            - '../src/Kernel.php'
```

### Délégation aux Use Cases

**❌ AVANT: Logique métier dans le controller**
```php
<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;

class ReservationController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $em, // ❌ Accès direct EM
    ) {}

    #[Route('/reservations/{id}/confirm', methods: ['POST'])]
    public function confirm(int $id): Response
    {
        // ❌ Logique métier dans le controller
        $reservation = $this->em->getRepository(Reservation::class)->find($id);

        if (!$reservation) {
            throw $this->createNotFoundException();
        }

        if ($reservation->getStatut() === 'annulee') {
            $this->addFlash('error', 'Réservation annulée');
            return $this->redirectToRoute('reservation_show', ['id' => $id]);
        }

        $reservation->setStatut('confirmee');
        $reservation->setConfirmedAt(new \DateTimeImmutable());

        $this->em->flush();

        // Envoi email
        // ...

        return $this->redirectToRoute('reservation_show', ['id' => $id]);
    }
}
```

**✅ APRÈS: Délégation au Use Case**
```php
<?php

declare(strict_types=1);

namespace App\Presentation\Controller\Web;

use App\Application\Reservation\UseCase\ConfirmReservation\ConfirmReservationCommand;
use App\Application\Reservation\UseCase\ConfirmReservation\ConfirmReservationUseCase;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ReservationController extends AbstractController
{
    public function __construct(
        private readonly ConfirmReservationUseCase $confirmReservation, // ✅ Use Case injecté
    ) {}

    #[Route('/reservations/{id}/confirm', name: 'reservation_confirm', methods: ['POST'])]
    public function confirm(string $id): Response
    {
        try {
            // ✅ Simple validation
            if (empty($id)) {
                throw $this->createNotFoundException();
            }

            // ✅ Création du Command
            $command = new ConfirmReservationCommand($id);

            // ✅ Délégation au Use Case (logique métier dans Application layer)
            $this->confirmReservation->execute($command);

            $this->addFlash('success', 'Réservation confirmée avec succès');

            return $this->redirectToRoute('reservation_show', ['id' => $id]);

        } catch (ReservationNotFoundException $e) {
            throw $this->createNotFoundException($e->getMessage());

        } catch (InvalidReservationException $e) {
            $this->addFlash('error', $e->getMessage());
            return $this->redirectToRoute('reservation_show', ['id' => $id]);
        }
    }
}
```

**Règles de délégation:**
- ✅ Controller crée le Command/Query
- ✅ Controller appelle le Use Case
- ✅ Controller gère uniquement la réponse HTTP (render, json, redirect)
- ✅ Controller gère les exceptions pour convertir en HTTP errors
- ❌ Pas de logique métier dans le controller
- ❌ Pas d'accès direct à `EntityManagerInterface`
- ❌ Pas de calculs métier (prix, statuts, etc.)

### Testing Strategy

#### Tests fonctionnels WebTestCase

```php
<?php

namespace App\Tests\Functional\Presentation\Controller\Web;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function it_displays_reservation_list(): void
    {
        // Given
        $client = static::createClient();

        // When
        $crawler = $client->request('GET', '/reservations');

        // Then
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('h1');
        $this->assertSelectorTextContains('h1', 'Réservations');
    }

    /**
     * @test
     */
    public function it_confirms_a_reservation(): void
    {
        // Given
        $client = static::createClient();
        $reservationId = $this->createTestReservation();

        // When
        $client->request('POST', "/reservations/{$reservationId}/confirm");

        // Then
        $this->assertResponseRedirects("/reservations/{$reservationId}");

        $client->followRedirect();
        $this->assertSelectorExists('.flash-success');
        $this->assertSelectorTextContains('.flash-success', 'confirmée avec succès');
    }

    /**
     * @test
     */
    public function it_returns_404_for_non_existent_reservation(): void
    {
        // Given
        $client = static::createClient();
        $nonExistentId = 'non-existent-uuid';

        // When
        $client->request('GET', "/reservations/{$nonExistentId}");

        // Then
        $this->assertResponseStatusCodeSame(404);
    }

    private function createTestReservation(): string
    {
        // Create fixture reservation for testing
        // ...
        return 'test-reservation-id';
    }
}
```

#### Tests Api Controllers

```php
<?php

namespace App\Tests\Functional\Presentation\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReservationApiControllerTest extends WebTestCase
{
    /**
     * @test
     */
    public function it_returns_reservation_as_json(): void
    {
        // Given
        $client = static::createClient();
        $reservationId = $this->createTestReservation();

        // When
        $client->request('GET', "/api/reservations/{$reservationId}", [], [], [
            'CONTENT_TYPE' => 'application/json',
        ]);

        // Then
        $this->assertResponseIsSuccessful();
        $this->assertResponseHeaderSame('Content-Type', 'application/json');

        $data = json_decode($client->getResponse()->getContent(), true);

        $this->assertArrayHasKey('id', $data);
        $this->assertArrayHasKey('montantTotal', $data);
        $this->assertArrayHasKey('statut', $data);
    }
}
```

### Commandes de validation

```bash
# 1. Déplacer les fichiers (manuel ou via git mv)
git mv src/Controller/ReservationController.php src/Presentation/Controller/Web/ReservationController.php

# 2. Mettre à jour namespaces (manuel avec éditeur)

# 3. Autoload
make composer CMD="dump-autoload"

# 4. Vérifier routes
make console CMD="debug:router" | grep reservation

# 5. PHPStan
make phpstan

# 6. Tests
make test-functional

# 7. Accès manuel
make up
# Ouvrir http://localhost:8080 et tester navigation
```

---

## Dépendances

### Bloquantes

- **US-001**: Structure Domain/Application/Infrastructure/Presentation créée (nécessite répertoire `Presentation/Controller/`)

### Bloque

- **US-009**: Créer structure Application avec Command/Query/Handler (controllers utiliseront les Use Cases)
- **US-020 à US-031**: Repository abstraction (controllers utiliseront les Repository interfaces via Use Cases)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 420-480, couche Presentation)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 45-73, problème controllers couplés)
- **Symfony Controllers:** [Documentation](https://symfony.com/doc/current/controller.html)
- **Symfony Routing:** [Documentation](https://symfony.com/doc/current/routing.html)
- **EasyAdmin:** [Documentation](https://symfony.com/bundles/EasyAdminBundle/current/index.html)
- **Clean Architecture** - Robert C. Martin, Chapitre 22 (Presentation Layer)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
