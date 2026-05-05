# Atelier Business — Préparation Sprint-006

> **Format**: 1 atelier global, ½ journée recommandée
> **Owners**: PO + Tech Lead
> **Cible**: sprint-006 (kickoff 2026-05-15)
> **Objectif**: clore les zones E (Estimable) et I (Independent) résiduelles du backlog INVEST + valider les déviations PRD/specs détectées par `/project:gap-analysis`.

---

## Décisions prises (Q1–Q13)

### Q1 — ProjectHealthScore — pondération composite (US-022)

| Item | Décision |
|------|----------|
| Pondération | **25/25/25/25** sur (budgetScore, timelineScore, velocityScore, qualityScore) |
| Mapping healthLevel | `[0,30]=critical / [30,60]=at-risk / [60,80]=healthy / [80,100]=excellent` |
| Statut | V1 — rebalancing futur possible si retours terrain |

### Q2 — Risk scoring rules (US-024)

5 signaux retenus (V1):

| # | Signal | Seuils |
|---|--------|--------|
| 1 | Budget consommé % | warn: >80% (high), >100% (critical) |
| 2 | Glissement planning (jours retard / jours initiaux) | warn: >20% (high), >40% (critical) |
| 3 | Marge projet % | warn: <10% (high), <0 (critical) |
| 4 | Score satisfaction contributeur | warn: <5/10 (medium) |
| 5 | Dépendances bloquées | warn: ≥1 dep critique (high) |

`riskLevel` final = max() des niveaux atteints.

### Q3 — Workload prediction algo (US-031)

| Item | Décision |
|------|----------|
| Méthode | Heuristique (pas ML V1) |
| Formule | `charge = somme(jours_staffés × proba_OrderStatus) / capacité_contributeur` |
| Seuils alerte | warn: >80% capacité hebdo, critical: >100% |
| Horizons | 3 / 6 / 12 mois (whitelist) |

### Q4 — Forecasting CA (US-062)

| Item | Décision |
|------|----------|
| Méthode | Moyenne mobile 6 mois × pondération devis |
| Formule | `forecast(N) = MA6(CA réel) × ajustement_pipeline(N)` |

**Probabilités OrderStatus pour pondération**:

| Statut | Probabilité |
|--------|------------:|
| PENDING (a_signer) | **défini par le commercial du compte** (champ `Order.winProbability`, défaut 50%) |
| WON (gagné) | 100% |
| SIGNED (signé) | 100% |
| LOST (perdu) | 0% |
| STANDBY | 0% |
| ABANDONED | 0% |
| COMPLETED | 100% (déjà facturé) |

**Schéma DB requis**: ajouter `Order.winProbability` (int 0-100, défaut 50).

### Q5 — Chatbot AI garde-fous (US-063)

| # | Décision |
|---|----------|
| 1 | Filtrage tenant **mandatory partout et tout le temps** dans chaque AI Tool (paramètre `companyId` injecté depuis security token) |
| 2 | Cross-tenant prompt: **refus poli + log + alerte sécurité** |
| 3 | Budget AI mensuel **par tenant** + **clés API par tenant** configurables |
| 4 | Provider strategy: **Anthropic prioritaire**, fallback selon clés saisies pour le tenant. Cascade actuelle conservée (Anthropic → OpenAI → Gemini) |

**Schéma DB requis**:
- `CompanySettings.aiKeysAnthropic`, `aiKeysOpenAi`, `aiKeysGemini` (chiffrés au repos via `EncryptedData` VO)
- `CompanySettings.aiMonthlyBudgetCents` (int)
- Nouvelle entité `AiUsageLog` (companyId, periodMonth, tokens, costCents, provider, createdAt)

### Q6 — Search backend (US-084)

| Item | Décision |
|------|----------|
| Backend | **MariaDB FULLTEXT** |
| Migration Doctrine | Index FULLTEXT sur: `client.name`, `client_contact.first_name/last_name/email`, `project.name/description`, `contributor.first_name/last_name/email`, `invoice.reference/notes`, `order.reference/title` |
| Mode SQL | `MATCH(...) AGAINST(:q IN BOOLEAN MODE)` |
| Cache | Redis 5 min sur queries fréquentes |

### Q7 — Validation vs Cascading (US-085 / US-086)

- US-085 reste = `ValidationController` validation atomique (5 types).
- **US-086 nouvelle** = `DependentFieldsController` cascading selects (Client→Projects→Tasks→SubTasks).
- FR-OPS-08 ajoutée à PRD.

### Q8 — Lead funnel fusion (US-012 ⊕ US-077)

- US-077 **mergée** dans US-012 (qui devient "Lead funnel" avec front capture + backend pipeline).
- US-012 passe de 5 → 8 pts.
- US-077 marquée `MERGED INTO US-012` (placeholder dans MKT.md).
- FR-CRM-03 + FR-MKT-03 consolidées sur US-012.

### Q9 — HubSpot 2 stories (US-013 + US-072)

- **Conservées séparées**:
  - US-013 (CRM workflow) = sync leads/contacts/deals, mapping métier, fréquence
  - US-072 (INT settings/connecteur) = credentials, test connexion, retry, circuit-breaker
- Dépendance: US-013 dépend de US-072 (settings doivent exister avant sync).

### Q10 — Sales dashboard (US-014 + US-061)

- Confirmées **indépendantes**.
- US-014 = dashboard ventes spécifique (KPIs commerciaux).
- US-061 = framework dashboards multi-roles (HR/Staffing/Treasury/Project-health).

### Q11 — Format atelier

- 1 atelier global (½ journée recommandée).

### Q12 — Sprint placement

- sprint-006 (kickoff 2026-05-15).

### Q13 — Owners

- PO + Tech Lead pour tous les sujets.

---

## Backlog d'implémentation issu des décisions

| # | Action | Module | Sprint cible | Owner |
|---|--------|--------|--------------|-------|
| 1 | Migration: ajouter `Order.winProbability` (int 0-100, défaut 50) | ORD | sprint-006 | dev BE |
| 2 | Migration: étendre `CompanySettings` (aiKeys×3 chiffrés, aiMonthlyBudgetCents) | IAM/AN | sprint-006 | dev BE |
| 3 | Nouvelle entité `AiUsageLog` + migration | AN | sprint-006 | dev BE |
| 4 | Migrations FULLTEXT sur 6+ colonnes (`ALTER TABLE ... ADD FULLTEXT`) | OPS | sprint-006 | dev BE |
| 5 | Implémenter formule composite ProjectHealthScore (V1 25/25/25/25) | PRJ | sprint-007 | dev BE |
| 6 | Implémenter ProjectRiskAnalyzer 5 signaux | PRJ | sprint-007 | dev BE |
| 7 | Implémenter WorkloadPredictionService (somme jours×proba/capacité) | PLN | sprint-007 | dev BE |
| 8 | Implémenter ForecastingService (MA6 × pondération devis) | AN | sprint-007 | dev BE |
| 9 | Garde-fous Chatbot AI (filter, refus, budget, fallback) | AN | sprint-007 | dev BE + sécurité |
| 10 | GlobalSearchService passe en MATCH AGAINST FULLTEXT | OPS | sprint-006 | dev BE |
| 11 | Tests cross-tenant chatbot (regression sécurité) | AN | sprint-006/007 | QA |
| 12 | US-077 cleanup MKT.md (déjà fait dans v2 INDEX) | docs | sprint-006 | docs |
| 13 | PRD update: FR-OPS-08, fusion FR-MKT-03+CRM-03, ROLE_COMMERCIAL | docs | sprint-006 | PO |

---

## Risques résolus / résiduels

| Gap-analysis ref | Risque | État après atelier |
|------------------|--------|---------------------|
| GAP-A1 §3 Goals & Metrics | OKR vide | toujours **ouvert** — décision PO restante |
| GAP-A2 §10 Glossary | vide | partiellement ouvert (ubiquitous language à compléter) |
| GAP-C1 multi-tenant SQLFilter | absent | **ouvert** — adressé dans US-005 sprint-006 (epic Security Hardening) |
| GAP-C2 voter coverage | thin | **ouvert** — adressé dans gap-analysis action #3 (sprint-006) |
| GAP-C3 ROLE_COMMERCIAL | orphan | **résolu** atelier — décider Option A (cleanup) ou B (wire). Tracker action #13. |
| GAP-C5 BC stubs (Reservation) | template | **ouvert** — décision atelier non explicitée. Recommandation: cleanup. |
| GAP-D1..D8 test gaps | divers | **ouverts** — adressés sprint-007/008+ |

---

## Critère de fin d'atelier

- [x] Q1–Q13 closes (13/13)
- [x] Stories US-022, US-024, US-031, US-062, US-063, US-084, US-085, US-086 mises à jour
- [x] Fusion US-012 ⊕ US-077 documentée
- [x] Séparation US-013 / US-072 documentée
- [x] Indépendance US-014 / US-061 confirmée
- [x] INDEX.md v2 régénéré
- [x] gate-backlog-validation v2 régénéré (85/85 PASS)
- [ ] PRD mis à jour avec FR-OPS-08 + fusion FR-MKT-03+CRM-03 (action #13)
- [ ] Décision finale ROLE_COMMERCIAL (Option A ou B)
- [ ] Décision finale BC stubs `Reservation/Catalog/Notification` (cleanup vs flesh-out)

---

## Suite

1. PR séparée pour migrations DB (#1, #2, #3, #4) — risque faible, peut partir avant le code applicatif.
2. PR séparée pour cleanup ROLE_COMMERCIAL (#13).
3. Sprint-006 planning poker — split candidates (US-005, US-012, US-013, US-015, US-016, US-020, US-022, US-029, US-031, US-061, US-062, US-063, US-072, US-073).
4. Re-run `/project:gate-validate-backlog` après split — confirmer 6/6 sur stories splitées.
