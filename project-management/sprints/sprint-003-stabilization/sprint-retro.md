# Rétrospective — Sprint 003 Stabilization (template)

> **Note** : pré-rempli en J1 avec les observations factuelles. Le format Starfish reste, à animer en plénière le **2026-05-25 16:30** après la review.

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | sprint-003-stabilization |
| Date retro | 2026-05-25 (cible) |
| Format | Starfish (Continuer / Commencer / Arrêter / Plus / Moins) |
| Facilitateur | Scrum Master |
| Durée | 1h30 |

## Directive Fondamentale (rappel)

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là. » — Norman Kerth

## Rappel sprint

- 30 / 30 pts livrés en J1 (sur-vélocité par rapport au plan 10 jours).
- Stack PR à 2 niveaux : #54 sur #50 (cs-fixer migration), #57 sur #56 (US-070+US-071 staging).
- 5 actions retro sprint-002 toutes livrées (OPS-002 à OPS-006) + dette TECH-DEBT-001/002 + provision staging.

## Observations Starfish — pré-remplies

### 🟢 Continuer
- **Stack PR explicite** sur stories liées (#50→#54, #56→#57) : review en cascade lisible.
- **Doc avant code** sur OPS-002, OPS-004, US-070 : la rédaction du `.md` a clarifié les actions externes nécessaires.
- **ADR avant migration** (OPS-003 → TECH-DEBT-002) : la décision écrite a évité un re-débat pendant la review du diff repo-wide.
- **Politique PR<400 immédiatement écrite (OPS-006)** : OPS-006 mergeable seul, indépendamment des autres.
- **`bash -n` syntax check sur shell scripts** avant push : pas un seul boot script cassé.

### 🟡 Commencer
- **Stacked PR review process** documenté : "le reviewer du #57 commence par #56 ; merge dans l'ordre 50→54 et 56→57".
- **CI rouge automatiquement signalée à l'auteur** sur la PR (post-OPS-004 mergé) : déjà fait pour main, étendre aux PRs au sprint-004.
- **Smoke test staging dans la CI** (post-deploy hook) : aujourd'hui smoke test = manuel, à automatiser via webhook Render → GitHub Actions.
- **Estimation poker plus stricte** : OPS-002 (3 pts) et OPS-006 (1 pt) ont pris < 2 h chacun ; ré-évaluer la maille.

### 🔴 Arrêter
- **Empiler 11 PRs en J1** : a créé un goulot review humain immédiat. Ralentir le débit ou paralléliser les reviewers.
- **Configurer le `MAILER_DSN` côté code** : c'est une config secret repo (Render dashboard / GitHub secret), à enlever du périmètre dev.
- **Tolérer les PHPCS warnings sur tests** : la passe TECH-DEBT-002 a auto-renommé des méthodes snake_case→camelCase, signe que la dette tests s'accumulait silencieusement.

### ⬆️ Plus de
- **PR atomiques < 400 lignes** : OPS-006 (1 pt, 36 lignes) a été mergeable instantanément.
- **Tests fonctionnels avec MailerAssertionsTrait** : TECH-DEBT-001 a démontré la valeur du pattern, à généraliser pour tout flow async.
- **Documentation produit dans le commit message** (sections "Hors scope", "Trade-offs") : utile pour le futur reader.

### ⬇️ Moins de
- **Stories `Could` poussées en J1** (US-071 dans sprint-003) : crée la pression de tout livrer même quand le scope ne le réclame pas. Garder `Could` comme tampon réel.
- **Fixtures personnelles dans les tests fonctionnels** : `provisionContributor` dupliqué entre VacationApprovalControllerTest et CancelNotificationFlowTest. À mutualiser.

## 5 Pourquoi à animer en plénière (sujet candidat)

**Problème** : 11 PRs ouvertes simultanément, toutes CI-rouges au moment de l'observation, créent un blocage review au lieu de fluidifier.

1. Pourquoi les 11 PRs sont rouges ? → Pré-existant repo-wide PHPCS/Mago/PHPUnit non absorbé.
2. Pourquoi non absorbé ? → TECH-DEBT-002 stack sur OPS-003 #50, dépend du merge en cascade.
3. Pourquoi en cascade ? → Pas de stratégie de cherry-pick / squash-merge planifiée.
4. Pourquoi pas planifiée ? → Le sprint a démarré avec les retro actions sans matrice de dépendance de merge.
5. Pourquoi pas de matrice ? → Pas formalisée dans `/workflow:start`.

**Cause racine candidate** : les commandes `/workflow:*` ne produisent pas de **matrice de merge** quand il y a stack PR. Ajouter à la rétrospective comme suggestion d'amélioration du workflow.

## Actions sprint-004 (à valider en plénière)

> 5 actions candidates ; vote en plénière pour garder les 3 prioritaires.

### Action 1 — Matrice de merge pour PRs stack
| Attribut | Valeur |
|---|---|
| Description | Documenter dans `CONTRIBUTING.md` la procédure de merge en cascade (squash, rebase --onto, merge --no-ff) quand il y a `> 1` niveau de stack. |
| Responsable | @tech-lead |
| DoD | Section "Merging stacked PRs" ajoutée + 1 PR de référence mergée selon la procédure. |

### Action 2 — Auto-comment CI rouge sur PR (extension OPS-004)
| Attribut | Valeur |
|---|---|
| Description | Étendre `ci-health-check.yml` pour commenter sur la PR rouge (en plus de l'issue main) avec un résumé des fails. |
| Responsable | @ops |
| DoD | Workflow étendu, premier run vert sur une PR factice. |

### Action 3 — Smoke test staging automatique post-deploy
| Attribut | Valeur |
|---|---|
| Description | Webhook Render `deploy.success` → GitHub Actions qui exécute `smoke-test-staging.sh`. Issue auto si rouge. |
| Responsable | @ops |
| DoD | 1 deploy réussit avec smoke test vert + 1 deploy avec smoke test rouge → issue ouverte. |

### Action 4 — Mutualiser fixtures fonctionnelles Vacation
| Attribut | Valeur |
|---|---|
| Description | Créer `tests/Support/VacationFunctionalTrait` factorisant `provisionContributor` + `submitAsEmployee` + `loginAs` utilisés par VacationApprovalControllerTest + CancelNotificationFlowTest. |
| Responsable | @dev-back |
| DoD | 2 tests fonctionnels migrés + diff de réduction visible. |

### Action 5 — Estimation poker rééchelonnée
| Attribut | Valeur |
|---|---|
| Description | Re-jauger l'échelle pour clarifier qu'OPS-006 type "1 pt" = "≤ 2 h" et OPS-002 "3 pts" peut être < 4 h. La cible reste 1 pt = 1 jour-personne, mais avec plus de granularité sur le low-end. |
| Responsable | @scrum-master |
| DoD | Note ajoutée dans `project-management/README.md` ; appliquée au sprint-004 backlog. |

## Suivi des actions sprint-002

| Sprint | Action | Statut |
|---|---|---|
| S-002 | Monitoring CI main (issue auto >24h) | ✅ livré dans OPS-004 #48 |
| S-002 | ADR Mago vs PHP-CS-Fixer | ✅ livré dans OPS-003 #50 |
| S-002 | Restaurer SonarQube | ✅ livré dans OPS-002 #53 |
| S-002 | Hooks no-Docker | ✅ livré dans OPS-005 #49 |
| S-002 | CONTRIBUTING.md PR<400 | ✅ livré dans OPS-006 #47 |

**5 / 5 actions retro sprint-002 livrées** dès le sprint suivant.

## Check-in / Check-out (à remplir)

| Membre | Check-in | Check-out | ROTI /5 |
|---|---|---|---|
| @dev-back | | | |
| @tech-lead | | | |
| @scrum-master | | | |
| @ops | | | |

## Prochaine étape

- Plénière 2026-05-25 16:30 : valider les observations, voter les actions.
- `/workflow:start 004` après priorisation backlog sprint-004.
