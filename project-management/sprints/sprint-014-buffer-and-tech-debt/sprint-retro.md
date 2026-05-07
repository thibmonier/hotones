# Sprint Retrospective — Sprint 014

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 014 — OPS Stabilization |
| Date | 2026-05-07 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Reshuffle PO mid-sprint** absorbé via PR docs séparée (#170). Pattern : story OPS injectée → re-engagement plan transparent → exécution sans casse vélocité. |
| K-2 | **PRs incrémentales US-087** (5 PRs : #171-#175) couvrant chacune une catégorie distincte (PHPStan, PHPUnit, Mago, E2E). Review focalisée + rollback granulaire possible. |
| K-3 | **Bug US-090 découvert via `curl` live** plutôt que par symptôme prod. Inspection proactive vaut mieux que monitoring passif. |
| K-4 | **Pragmatique sur Mago vs CS-Fixer** : `continue-on-error` accepté plutôt que tentative de réconciliation. Décision documentée dans US-087 PR. |
| K-5 | **Combiner Snyk + composer outdated** dans US-088 : 0 advisory + bumps patch en 1 PR. |
| K-6 | **Runbook Render** (`render-runbook.md`) écrit en même temps que le fix US-090. Documentation au plus près de l'incident, traçabilité maximale. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Buffer Vacation/Contributor ACL** non livré 4 sprints consécutifs (011/012/013/014). Plus du tout du buffer = backlog dette. | Sprint-015 : commitment FERME (8 pts). Promotion irrévocable. |
| L-2 | **Tests E2E DDD controllers** (testCreate*) cassent à chaque changement structure UC (CompanyContext ajouté → tests à recompiler). | Sprint-015 : ajouter Application Use Case test fixture builder pour découpler tests E2E des signatures UC. |
| L-3 | **Skips de tests** (Vacation CSRF + NotificationEvent) accumulent dette (33 skipped maintenant). | Sprint-016 ou EPIC-002 : story dédiée « réduction tests skipped à <10 ». |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Smoke test post-deploy automatique** sur fixtures Render via GitHub Action. | Bug US-090 a vécu 4 mois avant détection. Smoke test post-merge aurait détecté. |
| S-2 | **Décider Mago vs CS-Fixer** (ADR sprint-016). Maintenir les 2 = continue-on-error permanent = dette signal. | CS-Fixer = source de vérité actuelle. Mago apporte linter + analyzer mais format conflit. Choix structurel à acter. |
| S-3 | **EPIC-002 kickoff atelier PO** sprint-015 J1. Maintenant qu'EPIC-001 fini + sprint-014 stabilisation absorbée. | Stagnation possible entre 2 epics. Atelier scope = priorité absolue J1. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Considérer Vacation/Contributor ACL comme "buffer"**. | 4 sprints non-activé = dette ferme. Promu commitment irrévocable sprint-015. |
| ST-2 | **Créer fichiers statiques dans Dockerfile pour des routes Symfony**. | Bug US-090 est exemplaire : nginx try_files masque toujours le controller. À documenter en règle d'or équipe. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Vérification live (`curl https://prod/...`)** dans la Definition of Done de toute story OPS / déploiement. | Évite des bugs latents type US-090. |
| M-2 | **PRs incrémentales par axe technique** (vs PR fourre-tout). PR #171-#175 série US-087 = bon prototype. | Review focalisée, rollback granulaire, métriques par axe. |
| M-3 | **Runbook au plus près du fix** (cas par cas dans `docs/05-deployment/`). | Onboarding rapide + post-mortem auto-documenté. |

---

## 🎯 Actions concrètes Sprint 015

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Sprint-015 commitment FERME : Contributor + Vacation ACL (8 pts) + escalator step 5 (2 pts) + EPIC-002 kickoff (1 pt) = 11 pts | Tech Lead | Sprint-015 J1 |
| A-2 | EPIC-002 atelier PO + Tech Lead → MMF + 5 US candidates | PO + Tech Lead | Sprint-015 J1-J2 |
| A-3 | ADR sprint-016 : Mago vs CS-Fixer décision structurelle | Tech Lead | Sprint-016 affinage |
| A-4 | Story SMOKE-PROD-FIXTURES-ON-MERGE (déjà identifiée sprint-013 retro S-1) à scoper sprint-015 buffer ou sprint-016 | PO + Tech Lead | Sprint-015 J5 |
| A-5 | Story TESTS-SKIPPED-REDUCTION (33 → <10) à backloger | PO | Sprint-016 |

---

## 📈 Trends 7 sprints

| Sprint | Engagé | Livré | Focus |
|---|---:|---:|---|
| 008 | 26 | 26 | DDD Phase 1 (Client + Project) |
| 009 | 22 | 22 | DDD Phase 1 (Order) + Phase 2 ACL Client |
| 010 | 18 | 18 | DDD Phase 2 ACL Project |
| 011 | 14 | 14 | DDD Phase 2 ACL Order + Phase 3 controllers |
| 012 | 15 | 15 | DDD Phase 4 (Client) + Invoice Phase 2/3 |
| 013 | 11 | 11 | DDD Phase 4 complète (3 décom) + Coverage 35 % |
| **014** | **16** | **16** | **OPS Stabilization (CI green + Snyk + Render)** |

Cumul 7 sprints : **122 pts livrés**. Vélocité moyenne **17,4 pts/sprint**.

---

## Directive Fondamentale Norm Kerth

> « Quel que soit ce que nous avons découvert, nous comprenons et croyons
> sincèrement que chacun a fait du mieux qu'il pouvait, étant donné ce qu'il
> savait à ce moment-là, ses compétences et capacités, les ressources
> disponibles, et la situation. »

---

## Conclusion

Sprint-014 = **100 % livré, chaîne de prod stabilisée, bug critique
production fixé, 0 régression**.

EPIC-001 fini + chaîne CI/CD/Render verte = base solide pour démarrer
EPIC-002 sprint-015. Buffer héritage 4 sprints sera enfin traité.

Tendance 7 sprints : vélocité stable 17 pts, prédictible, sans dérive.
