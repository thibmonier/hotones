# ADR-0003 — Tolérance test legacy permanente : 3 fichiers Vacation `skip-pre-push`

## Statut

Accepté — 2026-05-05

## Contexte

Sprint-006 (TEST-FUNCTIONAL-FIXES-002) a auditer les **14 fichiers** de tests fonctionnels marqués `#[Group('skip-pre-push')]` pour décider, par fichier, soit un fix, soit une tolérance permanente formalisée par ADR.

Sur ces 14 fichiers :

- **5 ont été débloqués** (T-TFF2-01 à T-TFF2-03, PRs #100/#101/#102) après correction de bugs production sous-jacents :
  - `VacationType::choices()` inversé (form Symfony non submittable)
  - `MultiTenantTestTrait` UNIQUE collisions (emails/slugs réutilisés cross-tests)
  - `Foundry::createOne()->_real()` obsolète (Foundry v2 retourne l'entité directement)
  - `ProjectRepository:376` deprecation PHP 8.4 (`null` array offset)
- **3 fichiers Vacation** restent en échec malgré ces fixes :
  - `tests/Functional/Vacation/CancelNotificationFlowTest.php`
  - `tests/Functional/Controller/Vacation/VacationApprovalControllerTest.php`
  - `tests/Functional/Controller/Vacation/VacationRequestControllerTest.php`
- **6 fichiers** restants (Timesheet, Home, Analytics, OnboardingTemplate, MultiTenant, NotificationEventChain) sont **hors scope** de TEST-FUNCTIONAL-FIXES-002 — ils seront traités sprint-007+.

Erreur résiduelle commune sur les 3 Vacation :

```
Symfony\Component\HttpFoundation\Exception\SessionNotFoundException:
  There is currently no session available.
```

Origine : `static::getContainer()->get('security.csrf.token_manager')->getToken($id)` exécute le service CSRF dans le **container du test** (`KernelTestCase`), tandis que `KernelBrowser` (le client HTTP simulé) maintient sa propre session. Le `SessionTokenStorage` de CSRF cherche une session dans le `RequestStack` du container, qui n'a jamais reçu de requête HTTP — d'où `SessionNotFoundException`.

`testCancelOnPendingVacationFlashesSuccess` (corrigé partiellement en T-TFF2-01 par un `GET` warmup avant POST) illustre la limite : warmer le client n'établit pas de session dans le container statique. Symfony 7+/8+ a renforcé l'isolation request/container, ce qui rend l'ancien pattern (CSRF token via container) incompatible avec les tests fonctionnels CSRF-protégés.

`CancelNotificationFlowTest` ajoute un second axe : il vérifie que la chaîne event → message → notification fonctionne en transport synchrone (`messenger.transport.in_memory`). Les défaillances de session se cumulent avec d'éventuelles différences de stratégie de transport entre `dev` et `test`.

## Options évaluées

### Option A — Refactorer les tests pour bypass CSRF

- Créer un client de test sans CSRF (`framework.csrf_protection.enabled: false` en env `test`).
- ✅ Tests Vacation passent immédiatement.
- ❌ Réduit la couverture : on ne valide plus que CSRF est correctement câblé sur les routes mutables.
- ❌ Régression sécurité potentielle : si un nouveau formulaire arrive sans CSRF, le test fonctionnel ne le détectera plus.
- ❌ N'aide pas à terme — la même limite resurgira sur d'autres flows (Onboarding tasks, Project mutations, etc.).

### Option B — Refactorer les tests pour utiliser le crawler Symfony pour récupérer le token

```php
$crawler = $this->client->request('GET', '/mes-conges');
$token = $crawler->filter('input[name="_token"]')->attr('value');
```

- ✅ Tests CSRF-aware sans bypass.
- ✅ Plus représentatif du flow utilisateur réel (token vient du DOM, pas du container).
- ❌ Demande un re-write substantiel des 3 fichiers (~10 méthodes `generateCsrfToken()`).
- ❌ Pour `CancelNotificationFlowTest`, le problème n'est pas CSRF mais event→message dispatch — Option B ne résout pas tout.
- ❌ Charge non scopée à TEST-FUNCTIONAL-FIXES-002 (5 pts), risque dérive sprint-006.

### Option C — Tolérance permanente formalisée par ADR

- Marquer les 3 fichiers `skip-pre-push` comme **legacy tolérée** justifiée par cet ADR.
- Ces tests restent exécutés en CI (workflow `quality.yml` les inclut, ils ne sont skipés QUE en pre-push hook).
- Tracker un suivi futur (refacto CSRF/session test pattern) hors story TEST-FUNCTIONAL-FIXES-002.
- ✅ Pas de churn sprint-006.
- ✅ Pas de bypass sécurité.
- ✅ Couverture CI conservée.
- ❌ Dette technique reconnue, à reprendre quand le sujet "infrastructure de test" sera traité (candidat sprint-007 ou sprint-EPIC-001 phase migration BC Vacation→full DDD).

### Option D — Migrer vers tests d'intégration (Application layer)

- Bypass HTTP : tester directement `RequestVacationHandler`, `ApproveVacationHandler`, etc. (déjà fait dans `tests/Integration/Application/Vacation/*HandlerTest.php`).
- Conserver minimal `WebTestCase` Vacation pour le bonheur path principal (1 test par flow).
- ✅ Pas de problème CSRF/session.
- ❌ Les tests Functional existants couvrent des concerns spécifiques au controller (auth firewall, redirects, flash messages, scope tenant-side) qui ne se testent pas en Application layer.
- ❌ Suppression de tests non triviale, demande revue PO.

## Décision

**Option C retenue.**

Les 3 fichiers Vacation restent marqués `#[Group('skip-pre-push')]` comme **legacy tolérée permanente jusqu'à refonte de l'infrastructure de test CSRF/session**.

Le marker n'est pas une suppression — les tests s'exécutent toujours dans :
- Workflow GitHub Actions `quality.yml` (full PHPUnit).
- Workflow `ci.yml` (full PHPUnit).
- Manuellement via `vendor/bin/phpunit tests/Functional/Vacation/...`.

Le marker exclut uniquement le pre-push hook local (`make pre-push` / `lefthook` / `.githooks/`), pour éviter de bloquer les commits de devs face à une dette d'infrastructure.

## Conséquences

### Positives

- Sprint-006 ferme TEST-FUNCTIONAL-FIXES-002 dans son scope (5 pts) sans dérive.
- Les 5 markers retirés en T-TFF2-02/T-TFF2-03 prouvent que la majorité des `skip-pre-push` étaient des **bugs corrigeables** et non une dette structurelle.
- 2 bugs production (form `VacationType::choices()`, deprecation PHP 8.4) **trouvés et corrigés** par l'audit, livrables séparés du scope test.
- Couverture CI Vacation conservée — aucune régression de garantie.

### Négatives

- Dette technique reconnue : 3 tests ne tournent pas en pre-push local. Risque mineur que des PRs introduisent des régressions Vacation détectées seulement en CI.
- Les developers doivent être conscients de cette tolérance et **lancer manuellement** `vendor/bin/phpunit tests/Functional/Vacation/...` avant de pousser un changement sur :
  - Controller `Presentation/Vacation/Controller/*`
  - Handlers `Application/Vacation/Command/*Handler.php`
  - Domain `Domain/Vacation/Entity/*`
  - Form `Presentation/Vacation/Form/*`

### Suivi

- **EPIC-001 phase 2** (migration BC Vacation → DDD complet) inclura la refonte des tests fonctionnels Vacation pour adopter le pattern crawler-based CSRF (Option B). À ce moment, les 3 markers seront retirés et cet ADR sera annoté **Remplacé par la migration EPIC-001**.
- Si en sprint-007/008 la team identifie d'autres tests bloqués par le même boundary CSRF/session, **un seul refactor centralisé** des helpers (extension de `MultiTenantTestTrait` ou nouveau `SessionAwareTestTrait`) sera préféré à des fixes individuels.
- Aucune interdiction de déprioriser cet ADR si la dette devient gênante ; l'opt-in est clair (Option B prête à activer).

### Notes pour reviewers / nouveaux dev

Si tu vois `#[Group('skip-pre-push')]` sur un fichier `tests/Functional/...` :
- **Vérifier** si ce fichier est dans la liste des 3 ci-dessus → tolérance légitime, voir cet ADR.
- Sinon → c'est probablement une dette à éliminer (style T-TFF2-02 / T-TFF2-03) ; ouvrir une story dédiée plutôt que retirer le marker à l'aveugle.

## Références

- Sprint-006 task board : `project-management/sprints/sprint-006-test-debt-cleanup/sprint-goal.md`
- Story TEST-FUNCTIONAL-FIXES-002 (5 pts, Must)
- Tasks T-TFF2-01 (PR #100), T-TFF2-02 (PR #101), T-TFF2-03 (PR #102)
- EPIC-001 Migration Clean Architecture + DDD : `project-management/backlog/epics/EPIC-001-migration-clean-architecture-ddd.md`
- Symfony issue similaire : pattern CSRF + KernelBrowser (long-standing limitation)
