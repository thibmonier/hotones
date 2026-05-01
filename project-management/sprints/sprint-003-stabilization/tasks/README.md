# Sprint 003 — Tâches détaillées

| Cluster | Fichier | Stories | Pts |
|---|---|---:|---:|
| OPS / Qualité | [`OPS-tasks.md`](OPS-tasks.md) | OPS-002, OPS-003, OPS-004, OPS-005, OPS-006 | 11 |
| Produit / Tech-Debt | [`PRODUCT-tasks.md`](PRODUCT-tasks.md) | US-070, TECH-DEBT-001, TECH-DEBT-002, TEST-005, US-071 | 19 |

**Total : 30 pts** (capacité ajustée 32 pts, marge sécurité 4 pts).

## Conventions de nommage

| Préfixe | Cluster |
|---|---|
| `T-002-XX` | OPS-002 (Sonar) |
| `T-003-XX` | OPS-003 (Mago/CS-Fixer) |
| `T-004-XX` | OPS-004 (Monitoring CI) |
| `T-005-XX` | OPS-005 (Hooks fallback) — distinct de TEST-005 |
| `T-006-XX` | OPS-006 (PR<400) |
| `T-070-XX` | US-070 (Staging) |
| `T-D001-XX` | TECH-DEBT-001 (Notif annulation) |
| `T-D002-XX` | TECH-DEBT-002 (Cleanup CI) |
| `T-005T-XX` | TEST-005 — préfixe `T` ajouté pour éviter collision avec OPS-005 |
| `T-071-XX` | US-071 (Emails) |

> **Note collision** : sprint-003 a deux stories préfixées `005` (OPS-005 hooks + TEST-005 coverage). Les tâches utilisent `T-005-XX` pour OPS-005 et `T-005T-XX` pour TEST-005.
