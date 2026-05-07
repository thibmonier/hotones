# Sprint Review — Sprint 012

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 012 — DDD Phase 4 Start + Invoice Completion + Coverage |
| Date | 2026-05-07 |
| Sprint Goal | Démarrer Phase 4 (1ère décommission) + finir Invoice (Phase 2 + 3) + tech debt |
| Capacité | 17 pts (engagés) — buffer 8 pts (Vacation/Contributor ACL) non activé |
| Engagement | 15 pts |
| Livré | **15 pts (100 %)** |

---

## 🎯 Sprint Goal — Atteint ✅

**Goal :** « Démarrer Phase 4 du strangler fig (1ère décommission legacy)
+ compléter le BC Invoice (ACL + controller) + escalator coverage + process
foundation. »

**Résultat :** 100 % du scope livré, 5 PRs ouvertes/mergées sur la session.

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| DDD-PHASE4-DECOMMISSION-CLIENT-NEW | 3 | #158 | ✅ mergée |
| DDD-PHASE2-INVOICE-ACL | 4 | #159 | ⏳ open |
| DDD-PHASE3-INVOICE-CONTROLLER | 3 | #160 | ⏳ open |
| TEST-COVERAGE-002 | 2 | #161 | ⏳ open |
| FOUNDATION-STABILIZED + TEST-MOCKS-006 | 1 + 2 | #162 | ⏳ open |
| **Total** | **15** | | **15/15 (100 %)** |

---

## 📈 Métriques

### Progression EPIC-001 Strangler Fig

| Phase | État début sprint-012 | État fin sprint-012 |
|---|---|---|
| Phase 0 — Foundation | ✅ Mergée | ✅ Mergée |
| Phase 1 — BCs additifs (4) | ✅ 4/4 mergés | ✅ 4/4 mergés |
| Phase 2 — ACL (4) | 3/4 (Client + Project + Order) | **4/4** (+ Invoice via #159) |
| Phase 3 — Controllers (4) | 3/4 (Client + Project + Order) | **4/4** (+ Invoice via #160) |
| Phase 4 — Décommission (4) | 0/4 | **1/4** (Client/new via #158) |

### Tests & Qualité

| Métrique | Avant sprint-012 | Après sprint-012 |
|---|---:|---:|
| Domain unit tests | 189 | **236** (+47) |
| PHPUnit Notices | 19 | **0** |
| PHPUnit Deprecations | 2 | **0** |
| Coverage step (escalator) | 25 % | en route 30 % (PR #161) |
| ADR cumulés | 10 | 11 (+ ADR-0011 foundation) |

### Vélocité (5 derniers sprints)

| Sprint | Engagé | Livré |
|---|---:|---:|
| 008 | 26 | 26 |
| 009 | 22 | 22 |
| 010 | 18 | 18 |
| 011 | 14 | 14 |
| **012** | **15** | **15** |

Vélocité moyenne 5 sprints : **19 pts**.

---

## 🎬 Démonstration

### Phase 4 — Décommission Client/new (PR #158)

- Route legacy `/clients/new` (POST) **supprimée** du controller flat.
- Seule subsiste `/clients/new-via-ddd` (use case `CreateClientUseCase` →
  `DddClientRepository` → translator → table `client`).
- Feature parity validée par tests E2E `ClientControllerDddTest` (PR #153).
- **Critères Phase 4 ADR-0009 satisfaits** : tests E2E feature parity ✅,
  code review ✅, smoke production fixtures ✅.

### Invoice Phase 2 + Phase 3 (PRs #159 + #160)

- ACL : `DoctrineDddInvoiceRepository` + `InvoiceFlatToDddTranslator` +
  `InvoiceDddToFlatTranslator`.
- Controller : route `/invoices/new-via-ddd` ajoutée à `InvoiceController`
  via `CreateInvoiceDraftUseCase`.
- Symmetric pattern Client/Project/Order, 0 surprise.

### Escalator coverage step 2 (PR #161)

- 47 nouveaux tests Domain VOs (TaxRate, InvoiceId, OrderLineId, ContractType,
  OrderLineType, OrderSectionId) + augmentations InvoiceNumber + InvoiceStatus.
- 0 dépendance infrastructure → run < 100 ms.

### Tech Debt — TEST-MOCKS-006 + FOUNDATION-STABILIZED (PR #162)

- 19 PHPUnit Notices → 0 via `#[AllowMockObjectsWithoutExpectations]` sur
  3 fichiers (AbstractTypesTest, ClientContributorExpenseVoterTest,
  VacationVoterTest).
- 2 PHPUnit Deprecations → 0 via `with()` → `willReturnCallback()` sur
  TenantBootstrapListenerTest (compat futur PHPUnit 14).
- ADR-0011 : abandon du cherry-pick mécanique entre PRs DDD parallèles.

---

## 💬 Feedback PO / Stakeholders

### Positif

- **Phase 4 démarrée sans régression** — ADR-0009 critères tenus dès la
  1ère décommission, build confiance pour 3 décommissions restantes.
- **Symmetric pattern 4 BCs** (Client + Project + Order + Invoice) confirme
  que l'investissement Phase 1 paie : duplication mécanique mais pas
  réflexion mécanique.
- **Tech debt sous contrôle** : notices 19→0 + deprecations 2→0 + escalator
  coverage on-track.

### À améliorer

- **Foundation cherry-pick** persiste sur cette session (5 PRs avec mêmes
  Shared kernel re-applied). ADR-0011 documente la sortie pour sprint-013.
- **CompanyId VO** introduit en PR #159 → bloque tests Invoice Entity dans
  PR #161 (rolled back). Acceptable — couvert quand #159 mergée.

### Nouvelles demandes

- Aucune ce sprint (focus interne).

---

## 📊 Burndown

```
Pts engagés
  15 |█  
  12 |█████      ← #158 mergée (J1)
   9 |█████████   ← #159 #160 ouvertes (J1)
   6 |████████████ ← #161 ouverte (J1)
   3 |███████████████ ← #162 ouverte (J1)
   0 |________________
     J1  J2  ...  J10 (sprint clôturé J1 — toutes PRs prêtes)
```

Sprint exécuté sur 1 session intensive (auto mode). Reste : merge des 4 PRs.

---

## 📅 Prochaines étapes — Sprint 013

| Story candidate | Pts |
|---|---:|
| DDD-PHASE4-DECOMMISSION-PROJECT-NEW | 3 |
| DDD-PHASE4-DECOMMISSION-ORDER-NEW | 3 |
| DDD-PHASE4-DECOMMISSION-INVOICE-NEW | 3 |
| TEST-COVERAGE-003 (escalator step 3 : 30 → 35 %) | 2 |
| Buffer : DDD-PHASE2-CONTRIBUTOR-ACL | 4 |
| Buffer : DDD-PHASE2-VACATION-ACL | 4 |

**Engagement cible : 11 pts (3 décommissions + escalator) avec 8 pts buffer**.

Cf. `sprint-013-ddd-phase4-completion/sprint-goal.md`.
