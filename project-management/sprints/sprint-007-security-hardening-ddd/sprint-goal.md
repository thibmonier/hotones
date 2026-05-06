# Sprint 007 — Security Hardening + DDD Foundation

**Dates** : 2026-05-18 (lundi) → 2026-05-29 (vendredi). 2 semaines fixes (10 jours ouvrés).
**Capacité brute** : 8 j × focus 80% = ~32 pts.
**Origine** : sprint-006 retro actions ST1-ST3 + gap-analysis #1-#3 (Critical) + EPIC-001 phase 0.

## Sprint Goal

> **Implémenter le mécanisme d'isolation multi-tenant à la couche ORM (Doctrine SQLFilter) + voters d'autorisation entité sur les 8 BCs prioritaires + démarrer EPIC-001 phase 0 (audit + cherry-pick branche prototype DDD).**

## Rationale

Trois fronts critiques convergent sur sprint-007 :

1. **GAP-C1 (Critical) — Multi-tenant SQLFilter absent** : `grep` sur `src` confirme zéro `SQLFilter`/`TenantContext`/`TenantAware`. Le claim README "isolation complète des données" repose sur la discipline `WHERE company = :tenant` repository par repository — risque cross-tenant exposure dès qu'un dev oublie. Bloquant pour tout onboarding production additionnel.

2. **GAP-C2 (Critical) — Voter coverage thin** : 1 voter (`CompanyVoter`) pour 80 controllers. 127 `#[IsGranted]` annotations vérifient le rôle, **pas l'ownership entité**. Combinées à C1, fuite cross-tenant possible.

3. **EPIC-001 phase 0 — Migration Clean Architecture + DDD** : branche prototype `feature/sprint-001-clean-architecture-structure` (locale, datée 2026-01-18) contient 33 746 lignes scaffolding sur 9 BCs (User, Company, Client, BusinessUnit, Contributor, Order, Invoice, Timesheet, Project + Shared). Reprise par cherry-pick contrôlé recommandée vs réécrire from scratch.

## Capacité ajustée — coefficients par nature (OPS-016)

| Nature | Coefficient | Pts engagés | Pondéré |
|--------|------------:|------------:|--------:|
| feature-be | ×0.5 | 18 | 9 |
| infra | ×0.7 | 5 | 3.5 |
| test | ×0.8 | 6 | 4.8 |
| refactor | ×1.0 | 0 | 0 |
| doc-only | ×1.5 | 3 | 4.5 |
| **Total** | | **32** | **21.8** |

Capacité brute 32 × moyenne pondérée 0.68 → **capacité projetée ~22 pts**. Engagement 32 pts = stretch sprint, marge négative. Reality check : story SQLFilter peut splitter si scope dépasse.

## Cérémonies

| Cérémonie | Durée | Date / Récurrence |
|---|---|---|
| Atelier business pré-sprint | 1h | 2026-05-15 16:30 (post sprint-006 retro) |
| Sprint Planning Part 1 (QUOI) | 2h | 2026-05-18 09:00 |
| Sprint Planning Part 2 (COMMENT) | 2h | 2026-05-18 14:00 |
| Daily Scrum | 15 min/jour | 09:30 |
| Affinage Backlog (sprint-008 prep) | 1h | 2026-05-27 14:00 |
| Sprint Review | 2h | 2026-05-29 14:00 |
| Rétrospective | 1h30 | 2026-05-29 16:30 |

## User Stories sélectionnées

### Cluster Security Hardening (gap-analysis #1-#3)

| ID | Titre | Pts | MoSCoW | Nature | Origine |
|---|---|---:|---|---|---|
| SEC-MULTITENANT-001 | Implémenter `TenantContext` + `TenantFilter` Doctrine SQLFilter + `TenantAwareTrait` | 8 | Must | feature-be | gap-analysis GAP-C1 / US-005 |
| SEC-MULTITENANT-002 | Backfill `TenantAwareTrait` sur 50+ entités tenant-scoped | 5 | Must | refactor | suite SEC-MULTITENANT-001 |
| SEC-MULTITENANT-003 | Tests régression cross-tenant (Alice tenant A vs Bob tenant B) | 5 | Must | test | gap-analysis GAP-D1 |
| SEC-VOTERS-001 | Créer voters entité pour `Project`, `Order`, `Invoice`, `Timesheet` | 5 | Must | feature-be | gap-analysis GAP-C2 |
| SEC-VOTERS-002 | Créer voters entité pour `Vacation`, `Client`, `ExpenseReport`, `Contributor` | 3 | Should | feature-be | gap-analysis GAP-C2 |

### Cluster DDD Foundation (EPIC-001 phase 0)

| ID | Titre | Pts | MoSCoW | Nature | Origine |
|---|---|---:|---|---|---|
| DDD-PHASE0-001 | Audit branche `feature/sprint-001-clean-architecture-structure` (33 746 lignes scaffolding) | 2 | Must | doc-only | EPIC-001 phase 0 |
| DDD-PHASE0-002 | Cherry-pick contrôlé Shared kernel (Email VO, Money VO, AggregateRootInterface, DomainEventInterface) | 3 | Must | feature-be | EPIC-001 phase 0 |

### Cluster Test Quality (carryover sprint-006)

| ID | Titre | Pts | MoSCoW | Nature | Origine |
|---|---|---:|---|---|---|
| TEST-MOCKS-003 | Conversion `createMock` → `createStub` sur 22 classes Cas C (203 Notices → 0) | 1 | Could | test | TEST-MOCKS-002 carryover |

### Total

**32 pts engagés / capacité projetée 22 pts**. Stretch 145% — sprint à risque. Fallback si overflow : déférer SEC-VOTERS-002 (3 pts) ou DDD-PHASE0-002 (3 pts) en sprint-008.

## Cluster post-merge sprint-006

Action utilisateur (hors capa dev) :
- Merger les PRs sprint-006 ouvertes (#100-#112) si revues OK avant kickoff sprint-007.
- Décision PO sur ProjectHealthScore pondération (US-022, atelier business Q1 vs code 40/30/20/10).
- Provisionner secrets staging restants (post sprint-005 carryover : `STAGING_DATABASE_URL`, `STAGING_APP_SECRET`, `vars.STAGING_BACKUP_ENABLED`, `HUBSPOT_SANDBOX_TOKEN`, `vars.CONTRACT_TESTS_ENABLED`).

## Risques entrée sprint

| Risque | Sévérité | Mitigation |
|--------|----------|------------|
| `TenantFilter` introduit régression silencieuse | 🔴 high | SEC-MULTITENANT-003 tests régression cross-tenant **avant** déploiement staging |
| Cherry-pick branche prototype produit conflits massifs (main a divergé de 2218 fichiers) | 🟠 med | Worktree isolé pour Phase 0 ; bench 3 jours max sinon abandon cherry-pick et réécriture |
| Voter coverage scope dérive (8 BCs = beaucoup de tests + IsGranted refactor) | 🟠 med | Découpage en 2 stories (SEC-VOTERS-001 priorité 1, SEC-VOTERS-002 Should déférable) |
| Coefficient capacité 0.68 = stretch | 🟠 med | Reality check J3 ; déférer SEC-VOTERS-002 si dérive |
| Atelier business pré-sprint pas tenu | 🟡 low | Action retro sprint-006 ST1 — owner PO confirmé |

## Definition of Done (sprint-007)

Pour SEC-MULTITENANT-001 :
- [ ] `TenantContext` service implémenté + tests unitaires
- [ ] `TenantFilter extends SQLFilter` enregistré dans `doctrine.yaml`
- [ ] `TenantAwareTrait` créé (compatible Domain DDD futur)
- [ ] Listener active le filtre à `kernel.request` après `TenantMiddleware`
- [ ] Test régression : Alice tenant A → 0 résultats sur scope Bob tenant B
- [ ] PHPStan max passe
- [ ] PR review approuvée

Pour SEC-VOTERS-* :
- [ ] 1 voter par entité avec `supports()` + `voteOnAttribute()`
- [ ] Vérification (a) tenant match, (b) role grant, (c) ownership
- [ ] Tests cross-role + cross-tenant
- [ ] `denyAccessUnlessGranted` ajouté sur controllers concernés (au minimum sur les routes mutables)

Pour DDD-PHASE0-* :
- [ ] Audit `feature/sprint-001-clean-architecture-structure` documenté (rapport markdown)
- [ ] Liste de fichiers conservables / à réécrire / à abandonner
- [ ] Cherry-pick Shared kernel mergé sur main (sans conflit)
- [ ] Tests unitaires Shared kernel (Email VO, Money VO)

## Sprint-008 preview

- SEC-VOTERS-002 si déféré
- EPIC-001 phase 1 : migration BC `User` complète (Domain + Application + Infrastructure)
- EPIC-001 phase 2 : migration BC `Company` complète
- TEST-COVERAGE-001 sprint-008 cible 35% (cf escalier `docs/04-development/tests.md`)
- TEST-MOCKS-003 carryover (Notices → 0)
