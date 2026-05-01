# Sprint 004 — Tâches détaillées

| Cluster | Stories | Pts | MoSCoW |
|---|---|---:|---|
| Tests (gap-analysis Critical résiduels) | TEST-006, TEST-007, TEST-008, TEST-009 | 15 | Must (10) + Should (5) |
| Ops / Release tooling | OPS-007, OPS-008, OPS-009 | 8 | Must (2) + Should (6) |
| Dépendances / Sécurité | DEPS-001, DEPS-002, DEPS-003 | 8 | Must (3) + Should (5) |
| Refactor opportuniste | REFACTOR-001 | 2 | Could (2) |

**Total : 31 pts** sur capacité ~32 (focus 80% × 8 j × 5 personnes).

## DEPS-001 — Montée endroid/qr-code v5 → v6 (3 pts)

**Origine :** demande utilisateur sprint-003 J1.

### Tâches (8h)

| ID | Type | Tâche | Est. |
|---|---|---:|---:|
| T-DEPS-001-01 | [DEV] | `composer require endroid/qr-code-bundle:^6` + lecture du UPGRADE_FROM_5_TO_6 du package | 1h |
| T-DEPS-001-02 | [DEV] | Adapter le `config/packages/endroid_qr_code.yaml` à la nouvelle structure (writers / labels / logos) | 2h |
| T-DEPS-001-03 | [DEV] | Migrer les usages dans `src/Service/QrCodeService.php` (si présent) ou contrôleurs qui rendent un QR | 2h |
| T-DEPS-001-04 | [TEST] | Tests fonctionnels + visuels : un QR généré sur 2 cas connus reste scannable | 2h |
| T-DEPS-001-05 | [DOC] | Note de migration dans `docs/04-development/dependencies-migrations.md` | 1h |

### Definition of Done
- [ ] `composer.json` à `endroid/qr-code-bundle: ^6`
- [ ] Tous les usages QR adaptés
- [ ] Tests fonctionnels verts
- [ ] CI verte
- [ ] Note de migration ajoutée

### Notes
La v6 d'endroid/qr-code reorganise complètement la configuration : `writers`, `validators`, `factories`. Les tags `endroid_qr_code.qr_code_factory` ont été remplacés par `endroid_qr_code.builder`. Voir [le UPGRADE_FROM_5_TO_6.md officiel](https://github.com/endroid/qr-code/blob/master/UPGRADE.md).

---

## DEPS-002 — Symfony patches + roave/security-advisories audit (3 pts)

### Tâches (7h)
- T-DEPS-002-01 [DEV] `composer outdated --direct --strict | grep symfony` 1h
- T-DEPS-002-02 [DEV] `composer require ...` sur les patches non-breaking 2h
- T-DEPS-002-03 [TEST] Smoke test post-bump (CI verte + smoke test staging) 2h
- T-DEPS-002-04 [DOC] CHANGELOG entry 1h
- T-DEPS-002-05 [DEV] Vérifier `roave/security-advisories` à jour 1h

---

## DEPS-003 — composer audit + npm audit clean (2 pts)

### Tâches (5h)
- T-DEPS-003-01 [DEV] `composer audit` + résoudre les advisories restantes 2h
- T-DEPS-003-02 [DEV] `npm audit fix` (avec test smoke front) 2h
- T-DEPS-003-03 [DOC] Snapshot 0 vulnerability high/critical dans `docs/06-security/security-audit.md` 1h

---

## TEST-006 — Backup / Restore SQL strategy + tests integration (5 pts)

### Tâches (12h)
- T-006-01 [DEV] Commande `app:backup:dump` (mysqldump pour MariaDB / pg_dump pour Postgres staging) 3h
- T-006-02 [DEV] Commande `app:backup:restore` 2h
- T-006-03 [TEST] Test integration : dump → fresh DB → restore → assert data identique 4h
- T-006-04 [OPS] Cron quotidien staging (Render scheduler ou GitHub Actions cron) 2h
- T-006-05 [DOC] `docs/05-deployment/backup-restore.md` 1h

---

## TEST-007 — AlertSubscriber + ChartConfigService Twig (3 pts)

### Tâches (7h)
- T-007-01 [TEST] `tests/Unit/EventSubscriber/AlertSubscriberTest.php` (3 cas : niveaux d'alerte) 3h
- T-007-02 [TEST] `tests/Unit/Service/ChartConfigServiceTest.php` (chart configs courants) 3h
- T-007-03 [TEST] Smoke test functional : page dashboard rend les charts sans erreur Twig 1h

---

## TEST-008 — Healthcheck endpoint + Doctrine connectivity (2 pts)

### Tâches (5h)
- T-008-01 [DEV] `App\Controller\HealthCheckController::doctrine` qui vérifie `dbal:run-sql 'SELECT 1'` 2h
- T-008-02 [TEST] Test fonctionnel 200 si DB up, 503 sinon 2h
- T-008-03 [DOC] Mettre à jour `docs/05-deployment/health-checks.md` 1h

---

## TEST-009 — BoondManagerConnector + HubspotConnector integration tests (5 pts)

### Tâches (10h)
- T-009-01 [TEST] Mocks Guzzle handler + fixtures JSON pour BoondManagerConnector 4h
- T-009-02 [TEST] Idem HubspotConnector 4h
- T-009-03 [DOC] `docs/03-features/integrations.md` 2h

---

## OPS-007 — Stacked PR merge procedure (2 pts)

### Tâches (5h)
- T-007-01 [DOC] Section "Merging stacked PRs" dans `CONTRIBUTING.md` (squash, rebase --onto, merge --no-ff) 2h
- T-007-02 [DEV] Helper script `bin/merge-stack.sh` qui valide l'ordre et déclenche les merges via gh CLI 2h
- T-007-03 [TEST] Appliquer la procédure à un stack factice de 2 PRs 1h

---

## OPS-008 — Auto-comment CI rouge sur PR (3 pts)

### Tâches (7h)
- T-OPS-008-01 [OPS] Étendre `.github/workflows/ci-health-check.yml` avec un job `check-pr-status` qui scanne les PRs ouvertes 3h
- T-OPS-008-02 [OPS] Comment auto sur la PR : résumé "fail/total" + lien direct vers le run 3h
- T-OPS-008-03 [TEST] Test sur 1 PR factice 1h

---

## OPS-009 — Smoke test staging automatique post-deploy (3 pts)

### Tâches (7h)
- T-OPS-009-01 [OPS] Webhook Render `deploy.success` → endpoint GitHub Actions `repository_dispatch` 2h
- T-OPS-009-02 [OPS] Workflow qui exécute `docker/scripts/smoke-test-staging.sh` 2h
- T-OPS-009-03 [OPS] Issue auto si smoke test rouge 2h
- T-OPS-009-04 [TEST] 1 deploy success + 1 simulé rouge 1h

---

## REFACTOR-001 — Vacation fixtures trait (2 pts, Could)

### Tâches (4h)
- T-R001-01 [DEV] Créer `tests/Support/VacationFunctionalTrait` 2h
- T-R001-02 [DEV] Migrer VacationApprovalControllerTest + CancelNotificationFlowTest 2h
