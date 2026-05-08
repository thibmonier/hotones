# Sprint Review — Sprint 018

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 018 — EPIC-002 finition + dette technique |
| Date | 2026-05-08 (clôture anticipée — sprint J1) |
| Sprint Goal | Finir EPIC-002 (smoke prod étendu + correlation ID) + nettoyer dette technique sprints 016-017 + EPIC-003 scoping atelier PO |
| Capacité | 12 pts |
| Engagement ferme | 8.5 pts |
| Capacité libre | 3.5 pts |
| Livré | **10.5 pts (124 % engagement ferme)** |

---

## 🎯 Sprint Goal — Atteint partiellement ✅

**Goal :** « Finir EPIC-002 (smoke prod étendu + correlation ID frontend) +
nettoyer dette technique détectée sprints 016-017 (Invoice UC bug +
ContributorController DDD Phase 3 + chore gitignore). EPIC-003 scoping atelier
PO J3 pour prochain quarter. »

**Résultat :**
- ✅ EPIC-002 finition : US-096 X-Request-Id + SMOKE-PROD-EXTENDED livrés
- ✅ Dette technique : Invoice UC fix + Contributor DDD Phase 3 + chore gitignore
- ✅ TEST-COVERAGE-007 escalator step 7 livré (capacité libre)
- ⏳ **EPIC-003 atelier PO** : reporté sprint-019 (J1 dispo)

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| US-INVOICE-DRAFT-UC-INVOICENUMBER-INIT-FIX (sub-epic B) | 1 | #192 | ✅ mergée |
| US-096 X-Request-Id correlation ID (sub-epic A) | 2 | #193 | ✅ mergée |
| Contributor DDD route Phase 3 (sub-epic B) | 2 | #194 | ✅ mergée |
| SMOKE-PROD-EXTENDED login + dashboard + API DDD (sub-epic A) | 3 | #195 | ✅ mergée |
| chore-gitignore-save-db (sub-epic C) | 0.5 | #189 | ✅ mergée (héritage sprint-017) |
| TEST-COVERAGE-007 Domain Invoice Aggregate (capacité libre) | 2 | #196 | ✅ mergée |
| **Total** | **10.5** | | **10.5/8.5 ferme + 2/3.5 libre = 124 % ferme** |

### Reporté sprint-019

| Story | Pts | Raison |
|---|---:|---|
| EPIC-003 atelier PO J3 | 1 | Nécessite input user (5 questions arbitrage) |
| Buffer SMOKE étendu | — | Sub-epic A déjà couvert via #195 |

---

## 📈 Métriques

### EPIC-002 finition

| Story | Réalisations |
|---|---|
| US-096 X-Request-Id | `RequestIdSubscriber` (kernel.request priority 256 + kernel.response -10) + sync `ContextProcessor` + 10 tests Unit + sécurité whitelist `[A-Za-z0-9._-]+` 128 chars |
| SMOKE-PROD-EXTENDED | Job `smoke-extended` GH Actions : login form_login + CSRF extraction + dashboard 7 KPIs + API DDD `/api/contributors/active` + JSON shape validation + runbook on-call section dédiée |

### Dette technique nettoyée

| Item | Action |
|---|---|
| Invoice UC `isset()` typed property bug | `ReflectionProperty::isInitialized()` bypass property hook getter + 2 happy path tests Unit ré-activés |
| Contributor BC strangler fig | Phase 3 livrée : UC `ListActiveContributors` + DTO read-only + endpoint `GET /api/contributors/active` (séparé du Controller flat existant) + 4 tests Unit |
| `save-db/` PII gitignore | Push isolé sur main (héritage sprint-017) |

### Coverage escalator

| Step | Cible | Atteint | Tests |
|---|---:|---|---|
| Step 6 (sprint-017) | 50 % | ✅ | TEST-COVERAGE-006 + Invoice UC happy path ré-activé |
| **Step 7 (sprint-018)** | **55 %** | ✅ via 30 tests `Invoice` Aggregate Root | InvoiceTest.php (402 lignes) |

---

## 🚀 Vélocité 10 derniers sprints

| Sprint | Engagement | Livré | % |
|---|---:|---:|---:|
| 009 | 13 | 13 | 100 % |
| 010 | 13 | 13 | 100 % |
| 011 | 13 | 13 | 100 % |
| 012 | 13 | 13 | 100 % |
| 013 | 9 | 9 | 100 % |
| 014 | 12 | 12 | 100 % |
| 015 | 13 | 13 | 100 % |
| 016 | 11 | 11 | 100 % |
| 017 | 10 (ferme) | 13 | 130 % |
| **018** | **8.5 (ferme)** | **10.5** | **124 % ferme** |

Vélocité moyenne 10 sprints : 12 pts. Sprints 017 + 018 explosent vélocité
ferme (130 % puis 124 %). Tendance : capacité libre absorbée systématiquement.
Recalibrer engagement sprint-019 vers 11-13 pts ferme.

---

## 🎯 Démonstration

### EPIC-002 observabilité bout-en-bout
1. **Logging JSON** (US-095 sprint-017) : `extra.request_id` + `extra.user_email` + `extra.environment`
2. **Correlation ID** (US-096 sprint-018) : header `X-Request-Id` exposé frontend, lisible devtools
3. **Smoke extended** (sprint-018) : assertions business post-deploy automatiques

### Strangler fig BC Contributor
- Phase 1 ✅ (DDD entity)
- Phase 2 ✅ (ACL translators)
- Phase 3 ✅ (route DDD active sur lecture via UC)
- Phase 4 ⏳ (mutations via UC — futur sprint si traction)

### Coverage Domain Invoice
30 tests Unit sur Aggregate Root (state machine, edge cases, query methods, events).

---

## 💬 Feedback PO (à recueillir)

Questions à poser :
1. SMOKE-PROD-EXTENDED scope OK ou élargir create + delete project (pollution data acceptée) ?
2. EPIC-003 scoping atelier J1 sprint-019 — agenda 5 questions à arbitrer (cf sprint-019 kickoff)
3. Vélocité explosive 130 %/124 % deux sprints consécutifs : recalibrer engagement sprint-019 vers 12 pts ?

---

## 🔗 Liens

- PR #192 — Invoice UC fix
- PR #193 — US-096 X-Request-Id
- PR #194 — Contributor DDD Phase 3
- PR #195 — SMOKE-PROD-EXTENDED
- PR #196 — TEST-COVERAGE-007 Invoice Aggregate
- ADR-0012 — Stack observabilité
- Runbook on-call : `docs/05-deployment/oncall-runbook.md`
- Logging conventions : `docs/05-deployment/logging-conventions.md`
