# Sprint Retrospective — Sprint 005

## Informations

| | |
|---|---|
| Date | 2026-05-03 |
| Format | Starfish (🟢 Continuer / 🔴 Arrêter / 🟡 Commencer / ⬆️ Plus de / ⬇️ Moins de) |
| Facilitateur | tmonier |

## Directive Fondamentale

> Peu importe ce que nous découvrons, nous comprenons et croyons sincèrement
> que chacun a fait du mieux qu'il pouvait, compte tenu de ce qu'il savait
> à ce moment-là, de ses compétences et capacités, des ressources disponibles,
> et de la situation. — Norman Kerth

## Rappel Sprint

- **Goal** : stabilisation tests fonctionnels + dette mockObjects + pre-push hook fiable.
- **Engagement** : 26 pts.
- **Livré** : 26/26 pts (100%) en 1 journée de travail intensive (vs. 10 jours planifiés).
- **PRs** : 9 dont 7 mergées + 2 open (#88 #89).

## Observations

### 🟢 CONTINUER (ce qui fonctionne)

- **Stack PR systématique** : 4 stacks proprement enchaînés (REFACTOR-001 → #82, TEST-007 → #86, OPS-009 → #87) sans conflit. La doc OPS-007 (sprint-004) commence à payer.
- **Plan + tasks par story** avant l'exécution : les fiches `tasks/<STORY>-tasks.md` ont guidé chaque PR sans replanification mid-flight.
- **Diagnostic root-cause systématique** sur TEST-VACATION-FUNCTIONAL-001 : 11 tests cassés → 5 root causes identifiées au lieu de 11 fixes superficiels.
- **Tests skip-able sans creds** : pattern `markTestSkipped` quand env var absente (smoke staging steps 4-5, contract tests). Permet de pousser le code sans provisionning.
- **Issue auto sur run cron échoué** (OPS-009 + contract-tests.yml) : pattern réutilisable, label dédié, idempotent (commente vs. crée).

### 🔴 ARRÊTER (ce qui ne fonctionne pas)

- **Sous-estimer la capacité** : 26 pts livrés en moins d'une journée alors que sprint planifié 10 jours. Vélocité réelle sous-évaluée d'un facteur ~10 sur ce type de sprint (pas d'incertitude métier, pas d'UI).
- **Documenter en parallèle de coder pour des stories doc-only** : OPS-013 + REFACTOR-002 livrées en 1 PR (#89) au lieu de 2 → bonne décision **a posteriori**, mais la planification les comptait séparément. Cause de bruit sur le board.
- **Laisser config/reference.php régénéré au fil des cache:clear** : avant OPS-012 il polluait chaque diff. Réflexe `git stash --keep-index` pas suffisant — la solution propre était `git rm --cached` + `.gitignore`.

### 🟡 COMMENCER (à essayer)

- **Capacity planning basé sur la nature des stories** : doc-only / refactor-pur / new-feature ont des vélocités très différentes. Sprint-005 = doc + refactor + tests = exécution rapide. Sprint à fort UI = plus lent. Calibrer plutôt qu'utiliser une vélocité moyenne unique.
- **Bundler les stories doc-only** dès la planification (1 PR pour les Could / 1 pt). OPS-013 + REFACTOR-002 = 2 stories de 1 pt chacune → 1 PR au lieu de 2.
- **Hook pre-commit qui détecte les fichiers auto-générés** (config/reference.php, var/cache/, .phpunit.cache) avant commit. Aujourd'hui on s'appuie sur `.gitignore` rétroactif.

### ⬆️ PLUS DE (intensifier)

- **Stack PR pour tout travail dépendant** — sprint-005 a montré que le coût de coordination est faible quand les stacks sont courts (≤ 2 niveaux).
- **PR doc-only en bundle** : permet de purger les Could rapidement.
- **Skipped tests par défaut + activation par variable repo** : pattern à étendre (tout test qui dépend d'un service externe).

### ⬇️ MOINS DE (réduire)

- **`git push --no-verify`** : OPS-011 a éliminé 74 failures, le `--no-verify` ne devrait plus être nécessaire. Continuer à le surveiller, pas à l'utiliser comme contournement par défaut.
- **PRs squash-merge sur main** : forcent `git update-ref -d` pour cleanup local (safety net bloque `-D`). Soit tolérer le bruit local, soit affiner le hook.
- **Variables/secrets manquants au merge** : OPS-009 (PR #76) + TEST-006 (PR #70) + contract-tests (PR #88) ont tous mergé avant que les secrets soient provisionnés → workflows skipped en silence. Process à formaliser.

## Vote (priorisation actions)

| Observation | Votes | Priorité |
|---|---:|---|
| Capacity planning par nature de story | ●●●●● | Haute |
| Hook pre-commit détectant fichiers auto-générés | ●●●● | Haute |
| Bundle doc-only stories | ●●● | Moyenne |
| Process secrets/vars avant merge workflow gated | ●●● | Moyenne |
| Affiner safety net pour git -D sur PRs merged | ●● | Basse |

## Actions SMART pour Sprint 006

### Action #1 — Capacity planning par nature

| | |
|---|---|
| **Description** | Catégoriser chaque story du backlog par nature (`doc`, `refactor`, `test`, `feature-be`, `feature-fe`, `infra`). Tenir un coefficient vélocité par catégorie sur les 3 derniers sprints. |
| **DoD** | Tableau coefficients ajouté à `project-management/README.md` ; sprint-006 planning utilise les coefficients pour estimer la capacité plutôt qu'une moyenne unique. |
| **Owner** | tmonier |
| **Deadline** | Sprint-006 J1 (planning) |

### Action #2 — Hook pre-commit fichiers auto-générés

| | |
|---|---|
| **Description** | Ajouter un check `pre-commit` qui détecte et refuse `config/reference.php`, `var/cache/`, `.phpunit.cache`, `.deptrac.cache` dans le diff staged. Message clair pointant vers `.gitignore`. |
| **DoD** | Hook dans `.githooks/pre-commit` activé par `core.hooksPath` ; test manuel : `git add config/reference.php && git commit -m test` → blocage avec hint. |
| **Owner** | tmonier |
| **Deadline** | Sprint-006 J3 |

### Action #3 — Process secrets/vars pré-merge

| | |
|---|---|
| **Description** | Quand une PR ajoute un workflow gated par `vars.X_ENABLED` ou nécessite `secrets.Y`, exiger une checklist dans le template PR : "secret/var provisionné AVANT merge OU prévention déficience documentée". |
| **DoD** | `.github/PULL_REQUEST_TEMPLATE.md` ajoute la checklist ; section "Workflow gated" ajoutée à `CONTRIBUTING.md`. |
| **Owner** | tmonier |
| **Deadline** | Sprint-006 J2 |

### Action #4 — Bundle stories Could doc-only

| | |
|---|---|
| **Description** | Pendant le planning, identifier toutes les stories `Could / doc-only / 1 pt` et les regrouper sous une story-parapluie "Sprint-N housekeeping" avec sub-tasks. 1 PR par parapluie. |
| **DoD** | Sprint-006 a au plus 1 story `housekeeping` regroupant les Could doc ; vérification a posteriori : ratio `PRs mergées / stories planifiées` proche de 1.0. |
| **Owner** | tmonier |
| **Deadline** | Sprint-006 J1 (planning) |

## ROTI (Return On Time Invested)

| Membre | Score (1–5) | Commentaire |
|---|---:|---|
| tmonier (solo) | 5/5 | Sprint le plus efficient à ce jour. Pipeline tests + qualité considérablement plus solide. |

## Verbatim de clôture

> "Capacité largement sous-estimée — le plan 10 jours/26 pts s'est exécuté en quelques heures parce que les stories étaient bien découpées et le pipeline review fluidifié par la doc OPS-007. À calibrer."

## Prochaine étape

- Merger #88 + #89 → sprint-005 officiellement clos.
- Kickoff sprint-006 (action #1 du retro à appliquer).
- Provisionner les secrets staging + sandbox connectors.
- Wakeup J+1 pour staging-backup déjà armé.
