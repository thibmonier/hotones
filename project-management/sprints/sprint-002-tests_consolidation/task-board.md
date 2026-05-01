# Task Board — Sprint 002 Tests Consolidation

**Dates :** 2026-04-24 → 2026-05-08 (2 semaines — fériés FR 01/05, 08/05)
**Vélocité cible :** 34 points
**Mis à jour :** 2026-04-24
**Note :** OPS-001 partiellement livrée sur PR #30 depuis origin/main (composer scripts + README coverage). Voir section "Terminé" anticipée.

## Légende

- 🔲 À faire
- 🔄 En cours
- 👀 En review
- ✅ Terminé
- 🚫 Bloqué

---

## 🔲 À Faire (40 tâches, 84.5h)

### OPS-001 Coverage CI (2 pts, 4.5h) — ✅ DONE (2026-04-23)
| ID | Tâche | Est. | Réel | Statut |
|---|---|---:|---:|---|
| T-OPS001-01 | `phpunit.coverage.xml` + composer scripts | 1h | 0.25h | ✅ (scripts `test-coverage*`) |
| T-OPS001-02 | pcov Dockerfile.dev | 1h | 0h | ✅ Déjà fait (preexistant) |
| T-OPS001-03 | CI upload SonarCloud | 1h | 0h | ✅ Déjà fait (workflow sonarqube.yml) |
| T-OPS001-04 | README section coverage locale + CI | 0.5h | 0.25h | ✅ |
| T-OPS001-05 | Baseline + badge | 1h | 0h | ✅ Badge présent, baseline = 9.4% elements mesurée |

**Blocker PRE (non planifié, +2h):**
| PRE-VAC-01 | Fix 4 Doctrine Types Vacation (covariance DBAL 4.x) | - | 0.5h | ✅ |
| PRE-VAC-02 | Migrer 3 services legacy (HrMetrics, PlanningAssistant, StaffingMetrics) | - | 0.5h | ✅ |
| PRE-VAC-03 | Mapping embedded DateRange (séparer Entity/ + ValueObject/) | - | 0.5h | ✅ |
| PRE-VAC-04 | Fix `messenger.yaml` routing legacy path | - | 0.1h | ✅ |
| PRE-VAC-05 | Fix `OnboardingTemplateFixtures` empty deps | - | 0.1h | ✅ |
| PRE-VAC-06 | Fix `HrMetricsServiceTest` mock legacy class | - | 0.1h | ✅ |
| PRE-VAC-07 | `services.yaml` fixtures autowire | - | 0.1h | ✅ |

**Baseline coverage mesurée (sprint-001 J1):**
- Fichiers: 407 · Classes: 397 · Méthodes: 3575 · Statements: 27502
- Couverts: methods 399/3575 (**11.2%**) · statements 2523/27502 (**9.2%**) · elements 2922/31077 (**9.4%**)
- Tests: 288 tests, 1150 assertions, OK (17 errors → corrigés via PRE-VAC-06)

### TEST-001 Notifications (5 pts, 12h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-TEST001-01 | NotificationServiceTest unit | 3h | - |
| T-TEST001-02 | NotificationSubscriberTest integration | 4h | - |
| T-TEST001-03 | NotificationTypeEnumTest | 1h | - |
| T-TEST001-04 | Chain e2e event → notification | 3h | - |
| T-TEST001-05 | Doc 10 NotificationType | 1h | - |

### TEST-002 Auth/2FA (5 pts, 15h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-TEST002-01 | SecurityControllerTest functional | 4h | - |
| T-TEST002-02 | TwoFactorControllerTest functional | 3h | - |
| T-TEST002-03 | LoginSecuritySubscriberTest | 3h | - |
| T-TEST002-04 | Rate limiting login test | 2h | - |
| T-TEST002-05 | JWT login + Bearer test | 3h | - |

### TEST-003 RunningTimer (3 pts, 9h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-TEST003-01 | RunningTimer unit | 2h | - |
| T-TEST003-02 | Constraint unicité | 2h | - |
| T-TEST003-03 | Start/stop + conversion | 3h | - |
| T-TEST003-04 | Switch timer chain | 2h | - |

### TEST-004 Multi-tenant voter (3 pts, 8h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-TEST004-01 | CompanyVoterTest | 3h | - |
| T-TEST004-02 | CompanyContextTest | 3h | - |
| T-TEST004-03 | Cross-tenant audit test | 2h | - |

### US-066 Demande congés (5 pts, 14h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-066-01 | VacationRequestController | 3h | - |
| T-066-02 | Wire RequestVacationCommand | 2h | - |
| T-066-03 | Template Twig vacation/request | 3h | - |
| T-066-04 | Stimulus vacation_picker | 2h | - |
| T-066-05 | Functional test controller | 2h | - |
| T-066-06 | Integration test handler | 2h | - |

### US-067 Validation manager (5 pts, 13h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-067-01 | VacationApprovalController | 3h | - |
| T-067-02 | Wire Approve/Reject Commands | 2h | - |
| T-067-03 | Widget homepage pending | 3h | - |
| T-067-04 | Modal rejet Stimulus | 2h | - |
| T-067-05 | Functional test | 2h | - |
| T-067-06 | Test hors hiérarchie 403 | 1h | - |

### US-068 Rejet motif (3 pts, 5h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-068-01 | Endpoint reject + motif NotBlank | 1h | - |
| T-068-02 | UI formulaire rejet finalisé | 2h | - |
| T-068-03 | Test notification rejet | 2h | - |

### US-069 Annulation (3 pts, 4h)
| ID | Tâche | Est. | Assigné |
|---|---|---:|---|
| T-069-01 | Endpoint cancel | 1h | - |
| T-069-02 | Bouton UI annuler | 1h | - |
| T-069-03 | Test cancel owner + PENDING | 2h | - |

---

## 🔄 En Cours

| ID | US | Tâche | Démarré | Assigné |
|---|---|---|---|---|
| - | - | (vide) | - | - |

---

## 👀 En Review

| ID | US | Tâche | Reviewer |
|---|---|---|---|
| - | - | (vide) | - |

---

## ✅ Terminé

| ID | US | Tâche | Réel | Terminé |
|---|---|---|---|---|
| T-OPS001-01 | OPS-001 | phpunit.coverage.xml + composer scripts | 0.25h | 2026-04-23 |
| T-OPS001-02 | OPS-001 | pcov Dockerfile.dev (preexistant) | 0h | 2026-04-23 |
| T-OPS001-03 | OPS-001 | CI SonarCloud upload (preexistant) | 0h | 2026-04-23 |
| T-OPS001-04 | OPS-001 | README section coverage | 0.25h | 2026-04-23 |
| T-OPS001-05 | OPS-001 | Baseline 9.4% + badge présent | 0h | 2026-04-23 |
| PRE-VAC-01..07 | — | Unblock migration DDD Vacation | 2h | 2026-04-23 |

---

## 🚫 Bloqué

| ID | US | Raison | Action |
|---|---|---|---|
| - | - | (vide) | - |

---

## Métriques Sprint

### Progression

| Métrique | Cible | Actuel |
|---|---:|---:|
| Tâches terminées | 40+7 PRE | 12 (5 OPS + 7 PRE-VAC) |
| Heures estimées | 84.5h | - |
| Heures consommées | - | 2.5h |
| Heures restantes | 84.5h | 82h |
| Points livrés | 34 | 2 (OPS-001) |

### Coverage cible

| Métrique | Avant | Après Sprint 1 |
|---|---:|---:|
| Coverage code | ~14% | 20-25% (cible) |
| Modules avec tests | 6/12 | 10/12 |
| Infection MSI | N/A | mesuré baseline |
| PHPStan level | 4 | 4 (stable) |

### Burndown (à remplir progressivement)

```
Jour  | Restant estimé | Points restants
------|---------------:|----------------:
0     | 84.5h          | 34
1     | ?              | ?
2     | ?              | ?
...
10    | 0h (cible)     | 0 (cible)
```

---

## Ordre d'exécution recommandé

**Jour 1 :**
- 🏁 OPS-001 (fondation coverage CI)

**Jour 2-3 (parallèle, 2 devs) :**
- Dev A : TEST-001 + TEST-003
- Dev B : TEST-002 + TEST-004

**Jour 4-6 :**
- TEST-004 mergé → démarrer US-066
- TEST-002 mergé → déblocage final US-066

**Jour 7-9 :**
- US-066 fini → US-067 démarre
- US-068 + US-069 en parallèle sur fin US-067

**Jour 10-11 :**
- Stabilisation, bugs, review, smoke staging

**Jour 12 :**
- Sprint Review + Rétro

---

## Daily Scrum — format

Chaque jour 15min, pour chaque dev :
1. Hier : tâches terminées (T-XXX-YY)
2. Aujourd'hui : tâches démarrées
3. Blocages ?

---

## Risques

| Risque | Impact | Mitigation |
|---|---|---|
| Sous-estimation VacationRequestController | Retard US-066 | Découper si débordement J8 |
| Coverage CI casse builds | Blocage dev | Baseline tolérante initiale |
| Tests flaky Redis state | Instabilité | DAMA rollback + isolation |
| AI mock incomplet (impact TEST-001 indirect) | Tests fragiles | No live calls AI en CI |
| Migration pcov échoue | Coverage impossible | Fallback xdebug coverage mode |
