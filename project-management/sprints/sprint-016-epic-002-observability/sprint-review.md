# Sprint Review — Sprint 016

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | 016 — EPIC-002 Kickoff (Observabilité & Performance) |
| Date | 2026-05-07 |
| Sprint Goal | Atelier scope PO + Sentry config prod + smoke test post-deploy + rattrapage Contributor E2E |
| Capacité | 17 pts |
| Engagement | 11 pts ferme + 6 pts libre |
| Livré | **11 pts (100 %)** |

---

## 🎯 Sprint Goal — Atteint ✅

**Goal :** « Démarrer EPIC-002 (Observabilité & Performance) : atelier scope
PO finalisé, OpenTelemetry tracing intégré, smoke test post-deploy
automatique + rattrapage E2E Contributor BC. »

**Résultat :**
- Atelier PO sprint-016 J1 tenu — 5 questions arbitrées
- ADR-0012 stack observabilité écrit (Sentry free tier, option C différer)
- Sentry sampling 5 % configuré prod + staging (DSN env var Render)
- Workflow GH Action smoke test post-deploy auto
- 7 tests Integration Contributor ACL (rattrapage E2E pattern)

---

## 📦 User Stories Livrées

| Story | Pts | PR | Statut |
|---|---:|---|---|
| EPIC-002-KICKOFF (atelier + ADR-0012) | 1 | #185 | ✅ mergée |
| US-091 Sentry sampling 5 % prod | 5 | #185 | ✅ mergée |
| US-092 Smoke test post-deploy GH Action | 3 | #185 | ✅ mergée |
| TEST-CONTRIBUTOR-E2E | 2 | #186 | ✅ mergée |
| **Total** | **11** | | **11/11 (100 %)** |

---

## 📈 Métriques

### Atelier PO — 5 questions arbitrées

| # | Question | Réponse PO |
|---|---|---|
| 1 | Budget mensuel observabilité | C — Différer upgrade : free tier jusqu'à dépassement |
| 2 | Stack | Sentry free tier (déjà installé) |
| 3 | KPIs business | DAU/MAU + projets/jour + devis signés/mois + factures + conv + revenu 30j + marge moy projet (7 KPIs) |
| 4 | Cold start Render | Plan starter ($7/mois) |
| 5 | Smoke test scope | Minimum (homepage + /health) |

### Configuration Sentry post-deploy

```yaml
# config/packages/sentry.yaml when@prod
traces_sample_rate: 0.05    # 5 %
profiles_sample_rate: 0.10  # 10 % des transactions échantillonnées
send_default_pii: false     # RGPD safe
```

Volumétrie attendue : ~15k transactions/mois (sampling 5 %).

### Smoke test post-deploy

`.github/workflows/post-deploy-smoke.yml` :
- Trigger : push main + workflow_dispatch
- Wait /health 200 (max 5 min)
- Smoke 1/2 : `GET /` 200 + body contient "HotOnes"
- Smoke 2/2 : `GET /health` 200 + Content-Type ≠ application/octet-stream
  + body NE CONTIENT PAS '<?php' (régression US-090)

### Tests Integration Contributor ACL (+7 tests)

- findByIdLegacy + findByIdOrNull UUID guard + findByIdThrows
- findActive + findByManagerId
- saveAppliesDddChangesToFlat + savePureUuidThrows

### Vélocité (9 derniers sprints)

| Sprint | Engagé | Livré |
|---|---:|---:|
| 008 | 26 | 26 |
| 009 | 22 | 22 |
| 010 | 18 | 18 |
| 011 | 14 | 14 |
| 012 | 15 | 15 |
| 013 | 11 | 11 |
| 014 | 16 | 16 |
| 015 | 11 | 11 |
| **016** | **11** | **11** |

Cumul 9 sprints : **144 pts livrés**. Vélocité moyenne **16 pts/sprint**.

---

## 🎬 Démonstration

### Atelier PO + ADR-0012 (PR #185)

5 questions tranchées en session courte. ADR-0012 documente le contexte +
décisions + alternatives écartées + trigger upgrade Team plan.

### Sentry sampling prod (PR #185)

`config/packages/sentry.yaml` : sampling 5 % activé. `render.yaml` +
`render.staging.yaml` : SENTRY_DSN env var sync false (à définir manuel
dashboard Render — action utilisateur post-merge).

### Smoke test post-deploy (PR #185)

Workflow GH Action validé syntactiquement. À exécuter au prochain merge
main pour valider end-to-end. **Aurait détecté bug US-090 en moins d'1h
vs 4 mois latents** (assertion régression `<?php` raw source).

### Contributor ACL Integration tests (PR #186)

Pattern E2E rattrapé sur 5ème BC. Repository ACL exercé avec Doctrine
réel + tenant filter. 7 tests, run < 3s.

---

## 💬 Feedback PO / Stakeholders

### Positif

- **Atelier ciblé efficace** : 5 questions arbitrées en session unique.
  Format réutilisable pour futurs EPICs.
- **ADR-0012 trigger upgrade clair** : critères objectifs (quota 80 %,
  errors > 5/jour, > 30 users) plutôt que feeling.
- **Smoke test catches regressions historiques** : assertion régression
  US-090 explicite (`<?php` raw source) — pattern réplicable pour autres
  bugs résiduels.

### À améliorer

- **DSN Sentry pas auto-injecté** par render.yaml (sync: false manuel).
  Pas d'automation possible sans secret repo + build-time injection
  (overhead pas justifié au stade).
- **Contributor BC sans Phase 3 controller** : Integration test plutôt
  que vrai E2E HTTP. À reconsidérer si traction métier sur Contributor.

### Nouvelles demandes

Aucune ce sprint (focus EPIC-002 démarrage).

---

## 📅 Prochaines étapes — Sprint 017

| Story candidate | Pts | Priorité |
|---|---:|---|
| US-093 Dashboard 7 KPIs business | 5 | Must |
| US-094 Alerting Sentry → Slack `#alerts-prod` | 3 | Must |
| TEST-COVERAGE-006 (escalator post-EPIC-001 = 45 → 50 %) | 2 | Should |
| BUFFER : ContributorController DDD route Phase 3 (option) | 2 | Could |

**Engagement cible : 10 pts ferme** + 7 pts capacité libre.

Cf. `sprint-017-epic-002-dashboard-and-alerting/sprint-goal.md`.
