# Tâches — Sprint 001 Tests Consolidation

## Vue d'ensemble

| US / Item | Titre | Points | Tâches | Heures | Statut |
|---|---|---:|---:|---:|---|
| OPS-001 | Activer coverage CI | 2 | 5 | 4.5h | 🔲 |
| TEST-001 | Tests Notifications | 5 | 5 | 12h | 🔲 |
| TEST-002 | Tests Auth/2FA | 5 | 5 | 15h | 🔲 |
| TEST-003 | Tests RunningTimer | 3 | 4 | 9h | 🔲 |
| TEST-004 | Tests Multi-tenant voter | 3 | 3 | 8h | 🔲 |
| US-066 | Demande congés UI | 5 | 6 | 14h | 🔲 |
| US-067 | Validation manager UI | 5 | 6 | 13h | 🔲 |
| US-068 | Rejet avec motif | 3 | 3 | 5h | 🔲 |
| US-069 | Annulation | 3 | 3 | 4h | 🔲 |

**Total :** 9 items | 40 tâches | 84.5h

## Répartition par type

| Type | Tâches | Heures | % |
|---|---:|---:|---:|
| [OPS] | 3 | 3h | 3.5% |
| [BE] | 10 | 24h | 28.4% |
| [FE-WEB] | 6 | 13h | 15.4% |
| [TEST] | 19 | 41h | 48.5% |
| [DOC] | 2 | 1.5h | 1.8% |
| [REV] | - | 2h | 2.4% |

## Fichiers

- [VACATION-tasks.md](VACATION-tasks.md) — US-066, US-067, US-068, US-069
- [TESTS-tasks.md](TESTS-tasks.md) — TEST-001, TEST-002, TEST-003, TEST-004
- [technical-tasks.md](technical-tasks.md) — OPS-001 + tâches transverses

## Conventions

- **ID :** T-[item]-[numéro] (ex: T-066-01, T-TEST001-02)
- **Taille :** 0.5h – 8h max
- **Statuts :** 🔲 À faire | 🔄 En cours | 👀 Review | ✅ Done | 🚫 Bloqué
- **Vertical slicing :** Symfony (BE+FE) + MariaDB + PHPUnit (pas de Flutter — hors scope Sprint 1)

## Notes Sprint 1

- **Pas de migration DDD dans ce sprint** — US-066/067/068/069 réexposent UI sur Domain existant
- **TEST-004 bloque US-066** — voter tests avant exposition Vacation
- **TEST-002 Auth tests recommandés avant US-066** — sécurité éprouvée
- **OPS-001 premier** — mesure coverage baseline puis monitoring

## Dépendances externes

| Dépendance | Impact |
|---|---|
| Redis running | Tests Integration (Messenger, Rate limit) |
| MariaDB running | Tests Integration, Functional |
| SonarCloud SaaS | OPS-001 upload coverage |
| GitHub Actions | CI pipeline |
