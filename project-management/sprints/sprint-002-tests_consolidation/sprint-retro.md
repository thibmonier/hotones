# Rétrospective — Sprint 002 Tests Consolidation

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | sprint-002-tests_consolidation |
| Date retro | 2026-05-01 |
| Format | Starfish (Continuer / Commencer / Arrêter / Plus / Moins) |
| Facilitateur | Scrum Master |
| Participants attendus | équipe dev (PO absent par convention) |
| Durée | 1h30 |

## Directive Fondamentale (rappel)

> « Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait à ce moment-là, de ses compétences et capacités, des ressources disponibles, et de la situation. »
> — Norman Kerth

## Rappel Sprint

- **Sprint Goal** atteint : ✅ 100% — 34 / 34 pts livrés.
- **Stories** : OPS-001, TEST-001..004, US-066..069.
- **Bonus** livrés : Vacation DDD foundation (#32, #33), test-fixes 95 PHPUnit pre-existing (#38), planning docs (#31), deps bumps (#29 #41 #42).
- **Stack PR** : 9 mergées sur main + 1 en review (#43 US-068+069) + 1 doc review (#44).

---

## Observations — format Starfish

### 🟢 Continuer (ce qui fonctionne bien)

- **Stack PRs en chaîne** sur Vacation (#32 → #33 → #39 → #40 → #43) — branchements explicites, review claire en cascade.
- **Tests TDD avant UI** — TEST-001..004 mergés avant US-066..069 a évité de coder sur un socle non couvert.
- **Foundry + KernelTestCase + ResetDatabase** — pattern stable réutilisé sur tous les nouveaux tests intégration.
- **Documents de planning sur branche dédiée** (#31) — historique sprint clair et auditable.
- **Commits descriptifs avec sections « Out of scope »** — review humaine accélérée parce que le scope est explicite.
- **CSRF token IDs distincts** par opération (`approve{id}` / `reject{id}` / `cancel-manager{id}`) — empêche les replays cross-actions.

### 🟡 Commencer (nouvelles idées à essayer)

- **Pre-flight CI check** sur `main` avant chaque sprint planning — détecter les CI rouges « ambiantes » comme le crash Liip Imagine avant que ça bloque les PRs sprint.
- **Dependabot auto-merge** sur les bumps patch (security only) pour absorber #28 #29 #41 #42 sans frictionner les reviewers humains.
- **Captures d'écran ou GIF dans les PR descriptions** des stories UI (US-066..069) — accélère la review front + sert de support démo.
- **`gh secret list` audit trimestriel** — détecte les tokens expirés (SONAR_TOKEN cassé depuis fin 2025-11) avant qu'ils bloquent un sprint.
- **Décision archi écrite (ADR)** quand un conflit outil émerge (Mago vs PHP-CS-Fixer alignement clés tableaux) — évite de re-débattre.

### 🔴 Arrêter (ce qui ne fonctionne pas)

- **Self-approve PRs** comme blocage permanent — le reviewer humain externe est un goulot d'étranglement systématique. Solution : pair reviewer rotatif ou alternative auto-merge sur green CI.
- **Tests phpunit qui restent rouges depuis le sprint précédent** — 95 errors traînaient depuis l'upgrade PHPStan level 4→5 (commit `df8eed7`) avant que TEST-FIXES (#38) les débloque. Politique « on laisse couler » à abandonner.
- **Démos textuelles en lieu et place d'environnements staging** — le sprint-review parle d'env « à provisionner » alors que c'est une story sprint-001 (Walking Skeleton).

### ⬆️ Plus de (intensifier ce qui marche)

- **Tests intégration via vrais services container** (KernelTestCase + ResetDatabase) plutôt que mocks lourds — TEST-001 NotificationEventChainTest et TEST-003 RunningTimerRepositoryTest ont rapporté plus de signal qu'unit pur.
- **Refacto Domain DDD avant exposer UI** — la chaîne Vacation a fait gagner du temps sur US-066..069 parce que Application + Domain étaient déjà figés (#32).
- **Migrations Doctrine versionnées tôt** dans le sprint — `Version20260501100000.php` (US-068) ajoutée pendant le sprint plutôt que rattrapée.
- **Commits orientés `Out of scope`** — message commit sépare fix + dette pré-existante. Continue à rendre les reviews ciblées.

### ⬇️ Moins de (réduire sans arrêter)

- **PRs de plus de 500 lignes diff** : #32 (+2443/-1961) noyé la review et a forcé un PR de cleanup #33 immédiat. Cible : < 400 lignes par PR ou découper en commits atomiques visibles sur `gh pr diff`.
- **Hooks pre-commit/pre-push qui dépendent de Docker** alors que la moitié de la team développe sans Docker daemon up. Cible : fallback `composer phpcsfixer-fix` direct sans `docker compose run`.
- **Dépendances entre stories Vacation** (US-066 → US-067 → US-068 → US-069) en série stricte — empêche de paralléliser. Cible : isoler le motif rejet (US-068) du chemin manager (US-067) pour les rendre concurrentes au prochain sprint.

---

## Thèmes identifiés (clustering attendu en réunion)

### Thème A — CI / Qualité (votes attendus : ●●●●●)
- Liip Imagine kernel boot
- 95 PHPUnit pre-existing
- SONAR_TOKEN expiré
- Mago vs PHP-CS-Fixer alignement
- Hooks pre-commit / pre-push Docker-only

### Thème B — Process review / merge (votes attendus : ●●●●)
- Self-approve interdit
- PRs > 500 lignes
- Dependabot manuel sur patches sécurité

### Thème C — Stack PR / dépendances stories (votes attendus : ●●●)
- Chaîne #32 → #33 → #39 → #40 → #43
- US Vacation en série stricte vs parallèle

### Thème D — Démo / staging (votes attendus : ●●)
- Pas d'env staging utilisable pour sprint review
- Démo textuelle Gherkin en remplacement

---

## Analyse des 5 Pourquoi sur Thème A — CI rouge sur `main`

**Problème** : la CI sur `main` était rouge depuis fin avril (gh run #25151893434, 2026-04-30) avant que sprint-002 démarre, et on ne s'en est rendu compte qu'en investiguant OPS-001 PR #30.

1. Pourquoi la CI était rouge sur `main` ? → Liip Imagine refusait de boot le kernel parce que `public/assets/images` n'existait pas en CI.
2. Pourquoi le dossier n'existait pas ? → `/public/assets/` est gitignored.
3. Pourquoi le kernel essaie d'y accéder en CI ? → `phpstan-symfony` boot le Kernel via `tests/console-application.php` et liip_imagine.yaml exige le dossier au compile time.
4. Pourquoi personne n'a vu rouge avant le sprint ? → Aucun process de **monitoring CI sur `main`** — on n'investigue les échecs qu'au prochain PR sprint.
5. Pourquoi pas de monitoring ? → Convention implicite « tant que les PRs passent on s'en fout », non écrite.

**Cause racine** : absence de boucle de feedback automatique CI rouge sur `main` ⇒ **Action 1**.

---

## Actions Sprint-003

### Action 1 : Mise en place d'un monitoring CI sur `main`

| Attribut | Valeur |
|---|---|
| Description | Configurer un workflow GitHub Actions qui ouvre une issue automatique quand un job sur `main` passe rouge plus de 24h. Optionnellement notifier Slack. |
| Responsable | @tech-lead |
| Deadline | sprint-003 / Day 3 |
| DoD | Workflow committé + premier test fait en cassant volontairement un test sur `main` (PR temporaire) → issue ouverte + auto-fermée à la résolution. |
| Priorité | 🔴 Haute |

### Action 2 : ADR sur le conflit Mago / PHP-CS-Fixer

| Attribut | Valeur |
|---|---|
| Description | Rédiger `docs/adr/0002-mago-vs-php-cs-fixer-alignment.md` documentant la décision (désactiver `binary_operator_spaces` côté CS-Fixer ou la règle d'alignement côté Mago) avec justification. Mettre à jour la config gagnante. |
| Responsable | @dev-back |
| Deadline | sprint-003 / Day 5 |
| DoD | ADR mergée + CI verte sur PR de référence (test sur ancienne PR rouge sprint-002). |
| Priorité | 🟡 Moyenne |

### Action 3 : Restaurer le pipeline SonarQube

| Attribut | Valeur |
|---|---|
| Description | Régénérer un token SonarCloud avec scope « Execute Analysis », `gh secret set SONAR_TOKEN`, vérifier la première analyse verte sur PR factice, puis activer le Quality Gate strict côté SonarCloud (couverture min 10% sprint-003 → 80% long terme). |
| Responsable | @ops |
| Deadline | sprint-003 / Day 2 |
| DoD | SonarCloud affiche la baseline + le Quality Gate visible côté PR (status check). |
| Priorité | 🔴 Haute |

### Action 4 : Découpler les hooks pre-commit / pre-push de Docker

| Attribut | Valeur |
|---|---|
| Description | `.githooks/pre-commit` et `pre-push` détectent l'absence du daemon Docker et fallback sur `composer phpcsfixer-fix` / `composer test-unit` direct. |
| Responsable | @dev-back |
| Deadline | sprint-003 / Day 4 |
| DoD | `git commit` réussit sur une machine sans Docker tant que `vendor/` existe. |
| Priorité | 🟡 Moyenne |

### Action 5 : Politique PR < 400 lignes diff

| Attribut | Valeur |
|---|---|
| Description | Ajouter au `CONTRIBUTING.md` la règle : si `+...,-...` cumulés > 400 lignes (hors lockfiles, généré, snapshots), découper en commits atomiques ou en sous-PRs stack. Reviewer peut demander split avant review. |
| Responsable | @scrum-master |
| Deadline | sprint-003 / Day 1 |
| DoD | Section ajoutée + appliquée à la prochaine grosse PR (pas de merge si la règle est cassée sans justification écrite). |
| Priorité | 🟢 Basse |

---

## Suivi des actions précédentes (sprint-001)

> Aucune action sprint-001 n'a été enregistrée formellement (sprint-review et sprint-retro absents pour sprint-001). Première rétro **enregistrée** du projet.

| Sprint | Action | Responsable | Status |
|---|---|---|---|
| S-001 | — | — | ⚪ pas de retro précédente |

---

## Check-in / Check-out

À remplir en réunion (template) :

| Membre | Check-in (mood) | Check-out (« j'emporte… ») | ROTI /5 |
|---|---|---|---|
| @dev-front | | | |
| @dev-back | | | |
| @tech-lead | | | |
| @scrum-master | | | |
| @ops | | | |

ROTI moyen : à calculer en fin de session. Cible : ≥ 4/5.

Verbatims attendus à collecter :
- « Ce qui m'a surpris… »
- « Ce que je veux essayer au prochain sprint… »
- « Ce que je veux moins faire… »

---

## Prochaine étape

- Animer la séance plénière avec les observations ci-dessus comme matériau de départ.
- Après séance : mettre à jour les sections « Check-in / Check-out » + ajuster les actions selon le vote.
- Lancer `/workflow:start 003` une fois les 5 actions priorisées et le sprint-003 backlog confirmé.
