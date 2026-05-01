# Sprint 003 — Task Board (Kanban initial)

> Mise à jour à chaque Daily Scrum. Dernière update : 2026-05-01 (J0 — kick-off).

## Légende

| Icône | Statut |
|---|---|
| 🔲 | À faire |
| 🔄 | En cours |
| 👀 | Review (PR ouverte) |
| ✅ | Done (mergé sur main) |
| 🚫 | Bloqué |

## Sprint Backlog (30 pts)

| ID | Titre | Pts | MoSCoW | Assigné | Statut | PR |
|---|---|---:|---|---|---|---|
| OPS-002 | Restaurer SonarQube + Quality Gate | 3 | Must | @ops | 🔲 | — |
| OPS-003 | ADR Mago vs PHP-CS-Fixer | 2 | Must | @dev-back | 🔲 | — |
| OPS-004 | Monitoring CI `main` | 3 | Must | @tech-lead | 🔲 | — |
| OPS-005 | Hooks pre-commit/pre-push fallback no-Docker | 2 | Should | @dev-back | 🔲 | — |
| OPS-006 | CONTRIBUTING.md politique PR<400 | 1 | Should | @scrum-master | 🔲 | — |
| US-070 | Provision env staging | 5 | Must | @ops + @dev-back | 🔲 | — |
| TECH-DEBT-001 | Notifier intervenant annulation manager | 3 | Must | @dev-back | 🔲 | — |
| TECH-DEBT-002 | Cleanup CI résiduels (Mago/PHPCS/PHPUnit) | 3 | Should | @dev-back | 🔲 | — |
| TEST-005 | Coverage 9.4% → 25% | 5 | Should | @dev-back + @qa | 🔲 | — |
| US-071 | Email transactionnel Vacation | 3 | Could | @dev-back | 🔲 | — |

## Burndown (à mettre à jour quotidiennement)

```
Pts |
 30 |█████ J0 kickoff
 27 |
 24 |
 21 |
 18 |
 15 |
 12 |
  9 |
  6 |
  3 |
  0 |________________________________________________________________
    J1  J2  J3 (J4-J5 fériés) J6  J7  J8  J9  J10  J11
   11/05 12/05 13/05         18/05 19/05 20/05 21/05 22/05 25/05

Légende : ░░ idéal  ██ réel
```

## Cumulative Flow (à remplir J5 et J10)

```
US |
 10|
  8|
  6|
  4|
  2|
  0|________________________________________________________________
    J1  J2  J3  J6  J7  J8  J9  J10  J11

██ Done  ▒▒ Review  ░░ In Progress  __ To Do
```

## Bloqueurs identifiés

| Story | Bloqueur | Resp. levée |
|---|---|---|
| OPS-002 | SONAR_TOKEN à régénérer côté SonarCloud | @ops user-action |
| US-070 | Validation budget hosting Render | @po decision |
| US-071 | Choix provider mailer transactionnel | @ops decision |

## Sprint Goal — rappel

> **Stabiliser le pipeline CI/CD post-OPS-001, fermer la dette technique notifications du chemin Vacation, et provisionner un environnement staging démontrable pour les Sprint Reviews.**
