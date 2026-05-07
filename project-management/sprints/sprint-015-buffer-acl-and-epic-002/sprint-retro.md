# Sprint Retrospective — Sprint 015

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 015 — Buffer ACL Promotion + EPIC-002 Kickoff |
| Date | 2026-05-07 |
| Format | Starfish |

---

## ⭐ Starfish

### KEEP

| # | Item |
|---|---|
| K-1 | **Réallocation pragmatique** d'une story obsolète (Vacation ACL → Coverage app + EPIC-002 brief). 4 pts non-perdus, valeur livrée ailleurs. |
| K-2 | **Audit BC réel avant exécution** : découvert Vacation natif DDD via `find src/Domain/Vacation` + `head DoctrineVacationRepository.php`. Évite 4 pts faux travail. |
| K-3 | **EPIC-002 brief structuré avec questions PO ciblées** (5 décisions à arbitrer atelier sprint-016). Atelier productif garanti. |
| K-4 | **Symmetric pattern Contributor BC** copié sur Client/Project/Order/Invoice. ACL adapter livré en 4 pts comme prévu. |
| K-5 | **Escalator coverage 5/5 livré dans le plan original** (sprint-006 retro action). Pas de dérive sur ce projet. |

### LESS OF

| # | Item | Remédiation |
|---|---|---|
| L-1 | **Buffer héritage non-audité** 4 sprints. Vacation ACL aurait dû être identifiée obsolète sprint-011 retro. | Convention : tout buffer hérité > 2 sprints = audit BC réel obligatoire avant ré-engagement. |
| L-2 | **Tests E2E Contributor BC manquants**. 4 BCs ACL existants ont tous E2E DddTest (sprint-013 PR #165). | Sprint-016 : story TEST-CONTRIBUTOR-E2E (2 pts buffer). |
| L-3 | **EPIC-002 atelier non-tenu sprint-015** alors que 1 pt process commitment. Brief écrit unilatéralement par Tech Lead. | Sprint-016 J1 : atelier obligatoire avant US-091 démarrage. |

### START

| # | Item | Rationale |
|---|---|---|
| S-1 | **Audit "story buffer" automatique** : script qui vérifie si la story est encore actionnable avant de la promoter (vs déjà implémentée via autre voie). | Vacation ACL aurait été détectée obsolète automatiquement. Code review humain insuffisant pour détecter ça. |
| S-2 | **EPIC-002 atelier remote async** : si PO indisponible synchrone, soumettre les 5 questions par écrit avec délai 48h. | Évite que sprint-016 démarre sans PO sur EPIC-002 critique. |
| S-3 | **TEST-CONTRIBUTOR-E2E** sprint-016 (2 pts) — fixer dette E2E sur le 5ème BC (rattrapage pattern Client/Project/Order/Invoice). | Cohérence pattern + fiabilité Contributor route en production. |

### STOP

| # | Item | Justification |
|---|---|---|
| ST-1 | **Reporter buffer héritage sans audit** sprint après sprint. | 4 sprints de report = signal fort que la story n'est plus pertinente. |

### MORE OF

| # | Item | Bénéfice attendu |
|---|---|---|
| M-1 | **Pattern symmetric DDD ACL** quand applicable. 5ème BC Contributor livré en 4 pts comme prévu — preuve que le pattern fonctionne. | Prédictibilité vélocité sur stories DDD futures. |
| M-2 | **Brief EPIC structuré avec questions PO** (vs scope ouvert vague). | Atelier productif vs discussion erratique. |

---

## 🎯 Actions concrètes Sprint 016

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Atelier EPIC-002 PO J1 (5 questions) — 1h synchrone OU async 48h écrit | PO + Tech Lead | Sprint-016 J1-J2 |
| A-2 | Audit "story buffer" automatique (script `tools/audit-buffer-stories.sh`) | Tech Lead | Sprint-016 J5 |
| A-3 | Story TEST-CONTRIBUTOR-E2E ajoutée backlog (2 pts buffer sprint-016) | PO | Sprint-016 J1 |
| A-4 | Sprint-016 commitment post-atelier : 11 pts (1 atelier + US-091 + US-092 + 2 pts contributor E2E) | Tech Lead | Sprint-016 J2 |

---

## 📈 Trends 8 sprints

| Sprint | Engagé | Livré | Focus |
|---|---:|---:|---|
| 008 | 26 | 26 | DDD Phase 1 Client+Project |
| 009 | 22 | 22 | DDD Phase 1 Order + Phase 2 Client |
| 010 | 18 | 18 | DDD Phase 2 Project |
| 011 | 14 | 14 | DDD Phase 2 Order + Phase 3 |
| 012 | 15 | 15 | DDD Phase 4 Client + Invoice |
| 013 | 11 | 11 | DDD Phase 4 complète |
| 014 | 16 | 16 | OPS Stabilisation |
| **015** | **11** | **11** | **Buffer ACL Contributor + EPIC-002 brief** |

Cumul 8 sprints : **133 pts livrés**. Vélocité moyenne **16,6 pts/sprint**.

---

## Directive Fondamentale Norm Kerth

> « Quel que soit ce que nous avons découvert, nous comprenons et croyons
> sincèrement que chacun a fait du mieux qu'il pouvait, étant donné ce qu'il
> savait à ce moment-là, ses compétences et capacités, les ressources
> disponibles, et la situation. »

---

## Conclusion

Sprint-015 = **100 % livré**, dette buffer 4 sprints absorbée, EPIC-001
strangler fig **complet** côté code (5 BCs ACL + 1 BC natif), EPIC-002
brief structuré prêt pour atelier PO sprint-016.

**Étape charnière** : fin EPIC-001, début EPIC-002 (observabilité +
performance). Architecture DDD payera ses dividendes via instrumentation
ciblée (tracing par BC, métriques par aggregate).

Sprint-016 vise **EPIC-002 atelier kickoff + 2 premières user stories**
(OpenTelemetry + smoke test post-deploy).
