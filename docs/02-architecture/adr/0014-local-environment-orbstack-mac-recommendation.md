# ADR-0014 — Environment local Mac : OrbStack recommandé (vs Docker Desktop)

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-08 |
| Sprint | sprint-019 sub-epic D ENV-DOCKER-ALTERNATIVE |
| Story | sprint-018 retro A-5 |
| Auteur | Tech Lead |

---

## Contexte

Sprint-018 retro L-1 a documenté un crash Docker Desktop mid-sprint qui a
bloqué la chaîne pre-commit hook (PHPStan + CS-Fixer + PHPUnit en container).
Workaround `git commit --no-verify` adopté en exception, mais sprint-018 retro
ST-1 « stop --no-verify comme habitude » exige une solution durable.

Action sprint-018 retro A-5 : investiguer alternative Docker Desktop Mac.

---

## Décision

**Recommandation officielle Mac dev : OrbStack** (vs Docker Desktop).

**Fallback OSS si contrainte license : Colima** (drop-in compose-compatible).

---

## Comparatif décisionnel

| Critère | Docker Desktop | **OrbStack** | Colima |
|---|---|---|---|
| Stabilité observée Mac | ⚠️ Crash sprint-018 | ✅ Communauté unanime | ✅ Stable lima VM |
| Démarrage | ~30s | **~2s** | ~10s |
| Perf I/O volumes | Médiocre | **Native (Mac FS)** | Bonne |
| Compatibilité `docker compose` | 100 % | 100 % drop-in | 100 % drop-in |
| Coût solo dev | $0 | $0 | $0 |
| Coût Pro / dev | $5/mois ≥ 250 employés | $8/mois usage entreprise | $0 OSS |
| Friction migration | — (statu quo) | Faible | Faible |
| Risque éditeur | Faible (Docker Inc.) | Moyen (startup) | **Aucun (OSS)** |

---

## Trigger réversibilité

Revenir à Docker Desktop si :
1. OrbStack provoque > 2 incidents data-loss / mois (regression vs Docker Desktop)
2. Compose YAML existant incompatible (régression test pipeline)
3. Perf dégradée mesurable (`make test` > 2x temps Docker Desktop)

Switch vers Colima si :
1. License OrbStack Pro requise + budget non alloué
2. Politique entreprise interdit logiciel propriétaire
3. OrbStack disparaît (maintenance arrêtée)

---

## Conséquences

### Positives
- ✅ Pre-commit hook fonctionne fiablement → fin du `--no-verify` chronique
- ✅ DX améliorée : démarrage ~2s vs 30s (gain 28s × N restarts/jour)
- ✅ Perfs I/O ~10x sur volumes mounts (composer install, vendor scan)
- ✅ Compose YAML inchangé (zéro risque migration côté code)

### Négatives
- ❌ Recommandation Mac uniquement (Linux dev = Docker natif)
- ❌ License Pro $8/dev/mois si entreprise > revenu seuil → ré-évaluer
  quand traction commerciale
- ❌ Logiciel propriétaire → fallback Colima documenté pour résilience

---

## Action items

| ID | Action | Owner | Deadline |
|---|---|---|---|
| A-1 | Documenter migration step-by-step `docs/04-development/local-environment-mac.md` | Tech Lead | ✅ sprint-019 |
| A-2 | Self-install OrbStack côté dev | Chaque dev | Sprint-019 fin |
| A-3 | Sanity check pre-commit hook fonctionne sur OrbStack | Tech Lead | Sprint-019 fin |
| A-4 | Si > 2 mois sans `--no-verify`, considérer trigger résolu | Tech Lead | Sprint-021 retro |

---

## Alternatives écartées

### Docker Desktop (statu quo)
**Écarté** : stabilité Mac insuffisante (crash sprint-018), perfs I/O médiocres,
démarrage lent. Pas de raison de continuer après benchmark OrbStack.

### Podman Desktop
**Écarté** : `podman-compose` ≠ `docker compose` (gotchas réseau,
build-context). Migration coûteuse pour gain marginal.

### Lima brut (sans Colima wrapper)
**Écarté** : config manuelle complexe vs Colima qui automatise. Pas de gain
clair.

---

## Liens

- Doc migration : `docs/04-development/local-environment-mac.md`
- Sprint-018 retro : action A-5
- Pre-commit hook : `.githooks/pre-commit`

---

**Date de dernière mise à jour :** 2026-05-08
**Version :** 1.0.0
