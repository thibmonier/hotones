# User Stories Index — HotOnes (DRAFT v2)

> Reverse-engineered from codebase via `/project:reverse-stories`.
> Generated: 2026-05-04. Updated: 2026-05-04 (post atelier business 2026-05-15 simulé).
> Source: `project-management/scan-report.md` + `project-management/prd.md`.
> Status: **DRAFT** — every story marked `INFERRED`. Validated through `/project:gate-validate-backlog`.

---

## Headlines

| Metric | Value |
|--------|-------|
| Total stories actives | **85** |
| Stories merged | **1** (US-077 → US-012) |
| Stories nouvelles | **1** (US-086 cascading) |
| Total story points | **391** (+6 vs v1: fusion +3, US-086 +3) |
| Modules | **15** |
| FRs PRD couvertes | **86 / 86** (FR-OPS-08 ajoutée pour US-086) |

> **Pragmatic deviation**: 1 fichier par module (16 fichiers) avec stories complètes Card / 3C / Gherkin / INVEST / Estimation.

---

## Changelog v1 → v2 (post atelier 2026-05-15)

| Story | Changement |
|-------|------------|
| US-022 | Pondération composite 25/25/25/25 + healthLevel mapping (0-30 critical / 30-60 at-risk / 60-80 healthy / 80-100 excellent). E ✅ |
| US-024 | Risk scoring rules: 5 signaux (budget %, glissement, marge, satisfaction, deps bloquées). E ✅ |
| US-031 | Algo heuristique: charge = somme(jours × proba_OrderStatus)/capacité. Seuils 80/100. Horizons 3/6/12m. E ✅ |
| US-062 | Méthode = MA 6 mois × pondération devis. Probabilités: PENDING=commercial (champ `Order.winProbability`), WON/SIGNED=100%, LOST/STANDBY/ABANDONED=0%. E ✅ |
| US-063 | Garde-fous: tenant filter mandatory dans tools, refus+log+alerte cross-tenant, budget mensuel par tenant + clés API par tenant configurables, fallback Anthropic→OpenAI→Gemini selon clés tenant. Schéma `CompanySettings.aiKeys*`, nouvelle entité `AiUsageLog`. E ✅ |
| US-084 | Backend = MariaDB FULLTEXT (pas Meili/ES). Migrations FULLTEXT sur 6+ colonnes. E ✅ |
| US-085 | Périmètre: validation atomique uniquement (5 types). I ✅ |
| US-086 | **NOUVEAU** — Cascading dependent form fields (`DependentFieldsController`). 3 pts, Should. |
| US-012 + US-077 | **Fusion** en "Lead funnel" sur US-012 (5→8 pts). US-077 marquée MERGED. |
| US-013 + US-072 | Confirmées **séparées** (workflow CRM vs settings/connecteur tech). |
| US-014 + US-061 | Confirmées **indépendantes**. |

---

## MoSCoW distribution (v2)

| Priority | Count | Pts | % |
|----------|------:|----:|--:|
| Must | 45 | ~205 | 53% |
| Should | 33 | ~170 | 39% |
| Could | 7 | ~16 | 8% |
| **Total** | **85** | **391** | 100% |

---

## Module map (v2)

| Module file | Theme | Stories | Pts | FR range |
|-------------|-------|--------:|----:|----------|
| [IAM.md](IAM.md) | Identity, Access & Tenancy | 9 | 37 | FR-IAM-01..09 |
| [CRM.md](CRM.md) | CRM & Sales Pipeline (US-012 fused) | 5 | 29 | FR-CRM-01..05 + FR-MKT-03 |
| [ORD.md](ORD.md) | Quotes & Orders | 5 | 29 | FR-ORD-01..05 |
| [PRJ.md](PRJ.md) | Project Delivery | 9 | 43 | FR-PRJ-01..09 |
| [PLN.md](PLN.md) | Planning, Staffing & Workload | 5 | 29 | FR-PLN-01..05 |
| [TIM.md](TIM.md) | Time Tracking | 4 | 16 | FR-TIM-01..04 |
| [VAC.md](VAC.md) | Vacations (DDD CQRS BC) | 8 | 22 | FR-VAC-01..08 |
| [INV.md](INV.md) | Invoicing & Treasury | 6 | 26 | FR-INV-01..06 |
| [HR.md](HR.md) | HR & Performance | 8 | 36 | FR-HR-01..08 |
| [AN.md](AN.md) | Analytics, Forecasting & AI | 5 | 34 | FR-AN-01..05 |
| [NTF.md](NTF.md) | Notifications | 4 | 18 | FR-NTF-01..04 |
| [SAAS.md](SAAS.md) | SaaS Catalogue | 3 | 10 | FR-SAAS-01..03 |
| [INT.md](INT.md) | External Integrations | 3 | 19 | FR-INT-01..03 |
| [MKT.md](MKT.md) | Content & Marketing (US-077 merged) | 3 | 12 | FR-MKT-01..02, FR-MKT-04 |
| [OPS.md](OPS.md) | Operations & Platform | 8 | 26 | FR-OPS-01..08 |
| **Total** | | **85** | **391** | |

---

## Suggested MVP slice

| Module | Stories Must | Pts |
|--------|------|-----|
| IAM | US-001, US-002, US-004, US-005, US-006, US-007, US-008, US-009 | 32 |
| CRM | US-010, US-011 | 8 |
| ORD | US-015, US-016, US-018, US-019 | 24 |
| PRJ | US-020, US-025, US-026 | 18 |
| PLN | US-029, US-032, US-033 | 16 |
| TIM | US-034, US-036, US-037 | 11 |
| VAC | US-038..045 (BC complet) | 22 |
| INV | US-046, US-047, US-051 | 13 |
| AN | US-060, US-061 | 13 |
| NTF | US-065..068 | 18 |
| INT | US-074 (S3) | 3 |
| OPS | US-079, US-081, US-082 | 12 |
| **MVP** | **~45 stories** | **~190 pts** |

---

## Traceability matrix (FR ↔ US)

| FR | US | Module |
|----|----|--------|
| FR-IAM-01..09 | US-001..009 | IAM |
| FR-CRM-01..05 | US-010..014 | CRM |
| FR-ORD-01..05 | US-015..019 | ORD |
| FR-PRJ-01..09 | US-020..028 | PRJ |
| FR-PLN-01..05 | US-029..033 | PLN |
| FR-TIM-01..04 | US-034..037 | TIM |
| FR-VAC-01..08 | US-038..045 | VAC |
| FR-INV-01..06 | US-046..051 | INV |
| FR-HR-01..08 | US-052..059 | HR |
| FR-AN-01..05 | US-060..064 | AN |
| FR-NTF-01..04 | US-065..068 | NTF |
| FR-SAAS-01..03 | US-069..071 | SAAS |
| FR-INT-01..03 | US-072..074 | INT |
| FR-MKT-01, MKT-02, MKT-04 | US-075, 076, 078 | MKT |
| FR-MKT-03 | (fused → US-012) | CRM |
| FR-OPS-01..07 | US-079..085 | OPS |
| FR-OPS-08 (new) | US-086 | OPS |

---

## Confidence (post atelier)

| Area | Confidence |
|------|------------|
| FR↔US mapping | HIGH |
| Persona assignment | HIGH (post `ROLE_COMMERCIAL` cleanup décidé) |
| Acceptance criteria | HIGH (formules + seuils explicités atelier) |
| Estimation | MEDIUM (raffiner planning poker sprint-006) |
| MoSCoW | MEDIUM-HIGH |
| Overlaps & dependencies | HIGH (3 décisions atelier) |

---

## Next

1. ✅ Atelier business 2026-05-15 simulé via questions Q1–Q13.
2. 🔜 `/project:gate-validate-backlog` re-run → 85/85 PASS attendu.
3. 🔜 `/project:dependencies` graphe.
4. 🔜 sprint-006 planning poker pour valider points (US-005, US-013, US-072 = candidats split).
5. 🔜 Mettre à jour PRD §5 (FR-OPS-08, fusion FR-MKT-03 ↔ FR-CRM-03, ROLE_COMMERCIAL cleanup décidé).
