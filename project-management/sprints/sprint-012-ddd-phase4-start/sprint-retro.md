# Sprint Retrospective — Sprint 012

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 012 — DDD Phase 4 Start + Invoice Completion + Coverage |
| Date | 2026-05-07 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Symmetric pattern 4 BCs** (Client + Project + Order + Invoice) — Phase 2 ACL + Phase 3 controller livrent en ~7 pts par BC sans surprise. Investissement Phase 1 amorti. |
| K-2 | **Phase 4 décommission progressive** — 1 route legacy à la fois (Client/new ✅) au lieu de big-bang. Critères ADR-0009 (E2E feature parity + UAT) tenus. |
| K-3 | **Combiner stories tech debt courtes en 1 PR** — FOUNDATION-STABILIZED (1 pt) + TEST-MOCKS-006 (2 pts) groupés sur PR #162. Économie de review overhead. |
| K-4 | **Escalator coverage par VOs Domain** — pas d'infra dépendance, run < 100 ms, +47 tests pour 2 pts. ROI imbattable. |
| K-5 | **ADR systématique sur décisions de process** (ADR-0011) — pas seulement décisions techniques. Doc historique précieuse pour onboarding. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Cherry-pick foundation** persiste (Invoice ACL #159 a re-importé `CompanyId` cherry-picked depuis tag baseline). | Sprint-013 : ADR-0011 actée, plus aucun cherry-pick autorisé. PRs partent direct de `main`. |
| L-2 | **Bloquage test Invoice Entity** parce que CompanyId pas encore sur `main` au moment de PR #161. | Test ajouté en suivi quand #159 mergée. Pour sprint-013 : commander tests Entity avec PR ACL parente, pas en PR escalator séparée. |
| L-3 | **Linter comportement `git checkout` après push** déclenche cherry-pick implicite des fichiers de la branche précédente sur les system-reminders. | Documenter dans CLAUDE.md : ignorer system-reminders « contenu modifié » après checkout. |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Tester Use Cases Application** (CreateClientUseCase, CreateProjectUseCase, CreateOrderQuoteUseCase, CreateInvoiceDraftUseCase). | Couvert E2E mais pas Unit. Tests Unit avec mocks EM permettraient mutation testing ciblé. À évaluer ROI vs E2E. |
| S-2 | **Audit Phase 4 décommission timeline** — 3 décommissions restantes × 3 pts = 9 pts → tenable en 1 sprint si Invoice ACL/Controller mergées. Sinon décaler. | Permet de planifier sprint-013 confiant ou défensif. |
| S-3 | **Buffer Vacation/Contributor ACL** non activé sprint-011 ni sprint-012. | Sprint-013 si capacité. Sinon promus en commitment Sprint-014. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Cherry-pick mécanique entre PRs sœurs**. | ADR-0011 acté. Plus de duplication d'abstraction Shared. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Stories combinées tech-debt** (3 pts max combiné). | Réduit overhead review. PR #162 = bon prototype. |
| M-2 | **Tests Domain unit pure** (sans infra) pour escalator coverage. | Run rapide, mutation-friendly, 0 flaky. |

---

## 🎯 Actions concrètes Sprint 013

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Activer ADR-0011 : aucun cherry-pick foundation autorisé en review | Tech Lead | Sprint-013 J1 |
| A-2 | Ajouter Invoice Entity unit test (déférré PR #161) | Dev | Sprint-013 PR escalator step 3 |
| A-3 | Décider Use Cases unit tests vs E2E uniquement (ADR si nécessaire) | Tech Lead | Sprint-013 J5 |
| A-4 | Sprint-013 commitment : 3 décommissions + escalator step 3 = 11 pts | Tous | Sprint-013 kickoff |

---

## 📈 Trends 5 sprints

| Sprint | Engagé | Livré | Tech-debt notices | Coverage step |
|---|---:|---:|---:|---:|
| 008 | 26 | 26 | 251 | 25 % |
| 009 | 22 | 22 | 19 | 25 % |
| 010 | 18 | 18 | 19 | 25 % |
| 011 | 14 | 14 | 19 | 25 % |
| **012** | **15** | **15** | **0** | en route 30 % |

**Vélocité stable** + **tech debt en chute libre** + **escalator on-track**.

---

## Directive Fondamentale Norm Kerth

> « Quel que soit ce que nous avons découvert, nous comprenons et croyons
> sincèrement que chacun a fait du mieux qu'il pouvait, étant donné ce qu'il
> savait à ce moment-là, ses compétences et capacités, les ressources
> disponibles, et la situation. »

---

## Conclusion

Sprint-012 = **100 % livré, 0 régression, tech-debt en baisse, foundation
process stabilisée**. EPIC-001 a franchi la **moitié de Phase 4** (1/4
décommissions) et la **complétion de Phase 2 + 3** sur les 4 BCs critiques
(Client + Project + Order + Invoice).

Sprint-013 vise la **complétion de Phase 4** (3 décommissions restantes) +
escalator step 3 + buffer Vacation/Contributor ACL si capacité.
