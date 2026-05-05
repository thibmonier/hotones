# EPIC-001: Migration vers Clean Architecture + DDD

## Métadonnées
- **ID**: EPIC-001
- **Statut**: 🔴 To Do
- **Priorité**: **High**
- **MMF**: 1 bounded context legacy migré end-to-end vers Domain/Application/Infrastructure/Presentation, branche prototype existante `feature/sprint-001-clean-architecture-structure` réintégrée par cherry-pick contrôlé
- **Créé le**: 2026-05-05
- **Mis à jour**: 2026-05-05

## Description

Migration progressive de l'architecture HotOnes depuis le layout Symfony "classique" (`src/Entity` + `src/Controller` + `src/Repository` + `src/Service`) vers une **architecture Clean + DDD + Hexagonal** strictement conforme à `.claude/rules/02-architecture-clean-ddd.md` et aux patterns DDD prescrits par les règles 04 (Value Objects), 05 (Aggregates), 06 (Domain Events), 07 (CQRS), 09 (i18n), 10 (Async).

**Contexte brownfield**: l'analyse `/project:gap-analysis` (cf `project-management/analysis/gap-analysis.md` GAP-C6) a confirmé une architecture **hybride**: seul le bounded context `Vacation` est conforme DDD; les ~95% restants sont en code "Symfony classique" avec attributs Doctrine sur les entités du domaine, controllers fat, services orchestrant directement la persistance.

**Atout existant** ⚠️: une **branche de migration a déjà été entamée** — `feature/sprint-001-clean-architecture-structure` (commits `f1c21bf` + `21039f6`, datée 2026-01-18). Elle contient un scaffolding **substantiel** (33 746 lignes ajoutées, 160 fichiers): 9 bounded contexts en draft (User, Company, Client, BusinessUnit, Contributor, Order, Invoice, Timesheet, Project + Shared kernel), Value Objects auto-validés (UserId, ClientId, OrderId, Email, etc.), Domain Events, Repository interfaces, Doctrine Custom Types (ClientIdType, EmailType, etc.) et XML mappings (Client, Order, Money). Stale de plusieurs mois, **à reprendre par cherry-pick contrôlé** plutôt que merge brutal (main a divergé de 2218 fichiers / +499k lignes / -48k lignes depuis).

L'EPIC pilote le rebasing intelligent de cet acquis + l'extension à toutes les BCs identifiées dans la PRD (cf `project-management/prd.md` §5).

## Objectifs Business

- **Maintenabilité**: réduire le couplage Domain↔Infrastructure pour permettre l'évolution rapide des règles métier (formules de marge, scoring, alertes seuils) sans toucher à Doctrine ni à API Platform.
- **Testabilité**: rendre les agrégats testables unitairement sans BDD ni Symfony booté (gain x10 sur la rapidité de la suite, cf objectifs sprint-005/006 sur le test debt).
- **Sécurité**: isoler le domaine du multi-tenant filter (cf GAP-C1) et des voters (cf GAP-C2) — deux gaps critiques bloquants pour scale-up commercial.
- **Onboarding**: nouveau dev productif en J+5 grâce à un layering explicite (Domain pur PHP, indépendant des frameworks).
- **Évolutivité technique**: pouvoir basculer Doctrine ↔ Eloquent, Twig ↔ React, MariaDB ↔ Postgres avec impact contenu à l'Infrastructure.

## Bounded contexts cibles

| BC | Source legacy | Status branche prototype | Status actuel main | Priorité migration |
|----|--------------|--------------------------|---------------------|--------------------|
| **Vacation** | Migré | n/a | ✅ DDD complet (référence) | — |
| **User / Identity** | `src/Entity/User`, `src/Controller/Security`, `Security/Voter` | scaffolded | legacy | 🔴 P0 (lié GAP-C1/C2 multi-tenant + voters) |
| **Company / Tenant** | `src/Entity/Company`, `BusinessUnit`, `CompanySettings` | scaffolded | legacy | 🔴 P0 (lié US-005 multi-tenant SQLFilter) |
| **Order / Quote** | `src/Entity/Order` + `OrderLine/OrderSection/OrderTask` + `OrderController` | scaffolded (XML mapping prêt) | legacy | 🟠 P1 (state machine OrderStatus = candidat refacto majeur) |
| **Project** | `src/Entity/Project` + `ProjectTask/ProjectSubTask` + 8 controllers | scaffolded | legacy | 🟠 P1 |
| **Client / CRM** | `src/Entity/Client` + `ClientContact` + lead capture | scaffolded (Client.orm.xml prêt) | legacy | 🟠 P1 |
| **Timesheet** | `src/Entity/Timesheet` + `RunningTimer` | scaffolded | legacy | 🟠 P1 |
| **Invoice** | `src/Entity/Invoice` + `InvoiceLine` + treasury | scaffolded | legacy | 🟡 P2 |
| **Contributor / HR** | `src/Entity/Contributor` (+ Skills/Tech/Satisfaction/Progress) | scaffolded | legacy | 🟡 P2 |
| **Catalog / Marketing** | `BlogPost`, `LeadCapture`, `LeadMagnetController` | absent | legacy | 🟢 P3 |
| **Notification** | `src/Entity/Notification` + 11 NotificationType | absent | legacy + stub events sous `src/Event` | 🟡 P2 (intersection avec NTF stories) |

## User Stories prévues (à raffiner)

| ID | Nom | Statut | Points | Sprint |
|----|-----|--------|--------|--------|
| US-DDD-01 | Audit + cherry-pick branche `feature/sprint-001-clean-architecture-structure` (Shared kernel + VOs + Doctrine Types) | 🔴 To Do | 5 | sprint-007 |
| US-DDD-02 | Migration BC `User / Identity` complète (Domain + Application + Infrastructure + Presentation) | 🔴 To Do | 13 | sprint-008 |
| US-DDD-03 | Migration BC `Company / Tenant` + multi-tenant SQLFilter (lié US-005, GAP-C1) | 🔴 To Do | 13 | sprint-008 |
| US-DDD-04 | Migration BC `Order / Quote` + workflow OrderStatus state machine | 🔴 To Do | 13 | sprint-009 |
| US-DDD-05 | Migration BC `Project` + ProjectHealthScore service métier extrait | 🔴 To Do | 13 | sprint-010 |
| US-DDD-06 | Migration BC `Client / CRM` | 🔴 To Do | 8 | sprint-010 |
| US-DDD-07 | Migration BC `Timesheet` + workflow validation | 🔴 To Do | 8 | sprint-011 |
| US-DDD-08 | Migration BC `Invoice` + treasury | 🔴 To Do | 8 | sprint-011 |
| US-DDD-09 | Migration BC `Contributor / HR` | 🔴 To Do | 8 | sprint-012 |
| US-DDD-10 | Migration BC `Notification` (consolidation Event + NotificationType) | 🔴 To Do | 5 | sprint-012 |
| US-DDD-11 | Migration BC `Catalog / Marketing` (Blog + Lead) | 🔴 To Do | 5 | sprint-013 |
| US-DDD-12 | Activer Deptrac en gating CI (forbid `src/Entity` after migration done) | 🔴 To Do | 3 | sprint-013 |
| US-DDD-13 | Documenter ADR de migration + bilan + retro | 🔴 To Do | 2 | sprint-013 |

**Total estimé brut**: ~104 pts (~6 sprints à vélocité 32, ~12-14 semaines).

## Critères de Succès

- [ ] **Branche prototype réintégrée**: les patterns valides de `feature/sprint-001-clean-architecture-structure` (Shared kernel, VOs, Custom Types, XML mappings du Client) sont cherry-picked dans `main` ou réécrits si périmés.
- [ ] **Layering Deptrac vert**: la pipeline `make deptrac` passe sans violation pour toutes les BCs migrées.
- [ ] **Domain pur PHP**: aucune classe `src/Domain/**` ne dépend de Symfony, Doctrine ORM ou API Platform (validé par PHPStan + Deptrac).
- [ ] **Mappings hors Domain**: tous les mappings Doctrine sont en XML sous `src/Infrastructure/Persistence/Doctrine/Mapping/` (les attributs `#[ORM\Entity]` disparaissent de `src/Domain/**`).
- [ ] **Tests unitaires Domain rapides**: la suite Domain tourne en < 5s sans Symfony Kernel.
- [ ] **Couverture par BC**: chaque BC migré atteint > 85% de couverture line + > 80% MSI Infection sur le Domain layer.
- [ ] **Zero régression**: aucun test fonctionnel/E2E ne casse pendant la migration; chaque PR migre un sous-périmètre puis verrouille avec contract tests.
- [ ] **Voter coverage**: chaque agrégat migré expose au moins 1 voter d'autorisation entité (lié GAP-C2).
- [ ] **Multi-tenant SQLFilter**: opérationnel dès que BC `Company/Tenant` est en place (lié GAP-C1, US-005).
- [ ] **Documentation ADR**: 1 ADR par décision structurelle (Domain layout, mapping XML, CQRS scope, Aggregate boundaries).
- [ ] **Performance**: aucune dégradation > 10% des temps de réponse mesurés sur les endpoints critiques (`/projects`, `/clients`, `/treasury/dashboard`, `/api/orders`).

## Dépendances

- **Bloquants amont**:
  - GAP-C1 (multi-tenant SQLFilter absent) — devra être traité dans US-DDD-03 (ou en pré-requis sprint-006/007 via US-005).
  - GAP-C2 (voter coverage thin) — chaque BC migrée doit ajouter ses voters (consigne EPIC).
- **Bloquants aval**:
  - Suppression progressive de `src/Entity/`, `src/Controller/`, `src/Repository/`, `src/Service/` (legacy paths). Conditionnée par 100% de migration.
  - Activation Deptrac en CI gating (US-DDD-12).
- **Liens externes**:
  - Sprint-005 (test stabilization) en cours — la migration ne démarre PAS avant fermeture sprint-005 pour éviter casse parallèle.
  - Sprint-006 = security hardening (multi-tenant filter + voters) — fournit les fondations pour US-DDD-03.

## Approche technique

1. **Phase 0 — préservation**: cherry-pick `f1c21bf` dans une branche `chore/ddd-foundation-rebase` à partir de `main`. Conflits attendus → résoudre côté `main` (le code main est plus récent, à conserver) tout en intégrant les nouveaux fichiers `src/Domain/Shared/**`, VOs et Doctrine Types.
2. **Phase 1 — Shared kernel**: poser `App\Domain\Shared\ValueObject\{Email,Money,Country,DateRange,EncryptedData}` + `App\Domain\Shared\Interface\{AggregateRootInterface,DomainEventInterface}` + `App\Domain\Shared\Exception\DomainException`. Couvrir par tests unitaires.
3. **Phase 2 — User + Company first**: migrer en bloc (couplage fort User↔Company) car c'est le socle multi-tenant. Activer SQLFilter dès Phase 2 fin.
4. **Phase 3 — Order, Project, Client, Timesheet en parallèle**: 4 BCs avec dépendances faibles entre elles, possible parallélisation sur 2 devs.
5. **Phase 4 — Invoice, Contributor**: dépendent de Phase 3 (Order/Project/Timesheet pour Invoice; HR utilise les autres BCs).
6. **Phase 5 — Notification, Catalog**: dernier mile, BCs périphériques.
7. **Phase 6 — Cleanup**: suppression `src/Entity/`, `src/Controller/`, `src/Repository/`, `src/Service/` legacy. Activation Deptrac gating.

## Risques

| Risque | Sévérité | Mitigation |
|--------|----------|------------|
| Branche prototype trop stale → cherry-pick produit conflits massifs | 🔴 high | Phase 0 dédiée + worktree isolé (`parallel-worktrees`); bench: si > 3 jours, abandon de cherry-pick et réécriture from scratch en s'inspirant des patterns. |
| Dégradation perf via couches additionnelles | 🟠 med | Tests load avant/après chaque BC (Blackfire ou simple `make test:load`). |
| Régression silencieuse sur API publique | 🟠 med | Contract tests (`tests/Contract`) en gate CI; couverture des routes publiques. |
| Conflits avec sprint-005 test debt cleanup | 🟠 med | Migration démarre uniquement sprint-007+ après fermeture sprint-005/006. |
| Charge sous-estimée — 13 stories ~104 pts | 🟠 med | Découpage par BC + walking skeleton: 1 BC migrée prouve la formule avant les 9 suivantes. |
| Doublon `src/Domain/Notification` actuel (1 fichier) vs `src/Event` (8 events) | 🟡 low | Consolider dans US-DDD-10 (audit + fusion). |

## Progression

0/13 US complétées (0%)

---

## Notes

- **Branche prototype** identifiée: `feature/sprint-001-clean-architecture-structure` (local, non-pushée sur `origin`). 2 commits clés: `f1c21bf feat(domain): implement DDD Clean Architecture foundation (Sprint 001)` (2026-01-18) + `21039f6 docs(sprint): update backlog metrics`.
- **Avis** sur reprendre vs repartir: **reprendre par cherry-pick sélectif**. Économie estimée: 5-10 j-h sur le scaffolding initial. Les patterns sont conformes aux règles `.claude/rules/`. Le risque principal = conflits volumineux (main a évolué de 2218 fichiers depuis), donc tester en worktree isolé d'abord.
- **Référence DDD interne**: `src/Domain/Vacation/**` est l'implémentation actuelle complète; servir de modèle pour les autres BCs (Entity + ValueObjects + Repository interface + Domain Events + Exceptions). Compléter avec patterns Application (Command/Handler + Query/Handler + DTO) déjà en place dans `src/Application/Vacation/**`.
- Cf gap-analysis GAP-C6 pour le contexte du gap.
- Cf `project-management/analysis/atelier-business-prep.md` pour les décisions atelier qui touchent les BCs (formule HealthScore en US-DDD-05, Risk scoring en US-DDD-05, AI guardrails en US-DDD-10).
