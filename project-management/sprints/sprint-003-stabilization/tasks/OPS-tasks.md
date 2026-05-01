# Tâches — Cluster OPS (sprint-003)

## Contexte

5 stories OPS issues directement de la rétro sprint-002 (5 actions formelles).

---

## OPS-002 — Restaurer SonarQube + Quality Gate strict (3 pts)

**Persona :** P-002 Responsable qualité
**Origine :** retro action 3 + sprint-002 dette CI
**Dépend de :** régénération SONAR_TOKEN (action user)

### Tâches (8h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-002-01 | [OPS] | Régénérer token SonarCloud + `gh secret set SONAR_TOKEN` | 0.5h | — |
| T-002-02 | [OPS] | Tester analyse SonarCloud sur PR factice → succès HTTP 200 | 1h | T-002-01 |
| T-002-03 | [OPS] | Configurer Quality Gate "Sonar Way" + custom rules (couverture min 10%, duplication < 3%, no critical issue) | 2h | T-002-02 |
| T-002-04 | [DOC] | Documenter `docs/04-development/sonarqube.md` (badge, accès dashboard, troubleshooting 403) | 1.5h | T-002-03 |
| T-002-05 | [OPS] | Activer le check `SonarQube Quality Gate` comme **required** sur les protected branches | 1h | T-002-03 |
| T-002-06 | [TEST] | Vérifier que la sortie clover.xml de phpunit.coverage.xml remonte bien sur Sonar (delta vs sprint-002 baseline 9.4%) | 2h | T-002-02 |

### Definition of Done

- [ ] SonarCloud affiche un build vert sur la dernière PR sprint-003
- [ ] Quality Gate visible sur la page PR comme status check
- [ ] README projet contient le badge SonarCloud à jour
- [ ] Coverage delta entre sprint-002 et sprint-003 visible historisé

---

## OPS-003 — ADR Mago vs PHP-CS-Fixer formatage (2 pts)

**Persona :** P-002 Responsable qualité
**Origine :** retro action 2

### Tâches (5h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-003-01 | [DOC] | Rédiger `docs/adr/0002-mago-vs-php-cs-fixer-alignment.md` (contexte, options, décision, conséquences) | 2h | — |
| T-003-02 | [DEV] | Appliquer la décision : désactiver `binary_operator_spaces.operators.=>=align` côté `.php-cs-fixer.dist.php` OU désactiver la règle d'alignement côté `mago.toml`. **Décision à prendre en sprint planning P2.** | 1h | T-003-01 |
| T-003-03 | [DEV] | `composer phpcsfixer-fix` + `mago format-fix` sur tout le repo, commit du diff | 1h | T-003-02 |
| T-003-04 | [TEST] | Vérifier CI verte sur 3 PRs avec et sans alignement | 1h | T-003-03 |

### Decision matrix (pour P2)

| Option | Pro | Con |
|---|---|---|
| Désactiver alignement CS-Fixer | Mago est plus moderne, moins divergent | Diff énorme initial sur tout `src/` |
| Désactiver alignement Mago | Diff initial limité, équipe habituée à CS-Fixer | Mago perd un de ses arguments forts |
| Garder les deux + `continue-on-error: true` Mago | Pas de churn | Dette permanente, signal CI dilué |

---

## OPS-004 — Monitoring CI `main` (3 pts)

**Persona :** P-001 Tech Lead
**Origine :** retro action 1 + 5 Pourquoi cause racine

### Tâches (8h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-004-01 | [OPS] | Créer `.github/workflows/ci-health-check.yml` : cron horaire qui appelle `gh run list --branch main --status failure --limit 1 --created ">24h ago"` | 2h | — |
| T-004-02 | [OPS] | Si rouge >24h : créer issue auto avec label `ci-incident`, lien vers le run, assignation @oncall | 2h | T-004-01 |
| T-004-03 | [OPS] | Si rouge se résout : ajouter commentaire + fermer l'issue auto | 1h | T-004-02 |
| T-004-04 | [DOC] | Documenter le process dans `docs/04-development/ci-monitoring.md` | 1h | T-004-02 |
| T-004-05 | [TEST] | PR test : casser volontairement un test sur `main` (commit puis revert), observer issue ouverte/fermée | 2h | T-004-03 |

### Definition of Done

- [ ] Workflow committé sur `main`
- [ ] Premier test fait et issue ouverte/fermée correctement
- [ ] Doc référencée depuis `CONTRIBUTING.md`

---

## OPS-005 — Hooks pre-commit / pre-push fallback no-Docker (2 pts)

**Persona :** P-003 Dev sans Docker daemon
**Origine :** retro action 4 + observations sprint-002 `--no-verify` répétés

### Tâches (5h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-005-01 | [DEV] | `.githooks/pre-commit` : détecter daemon Docker via `docker compose version` ; sinon `composer phpcsfixer-fix` direct | 1.5h | — |
| T-005-02 | [DEV] | `.githooks/pre-push` : même fallback pour `composer test-unit` | 1.5h | T-005-01 |
| T-005-03 | [DOC] | Mettre à jour `CONTRIBUTING.md` avec instructions install hooks + comportement attendu | 1h | T-005-02 |
| T-005-04 | [TEST] | Tester sur machine sans Docker (CI Mac runner ou container Alpine) | 1h | T-005-02 |

---

## OPS-006 — Politique PR <400 lignes diff (1 pt)

**Persona :** P-001 Tech Lead
**Origine :** retro action 5 + observation PR #32 (+2443/-1961)

### Tâches (2h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-006-01 | [DOC] | Ajouter section "PR Size Policy" à `CONTRIBUTING.md` (cible <400, exclus lockfiles/snapshots/migrations auto) | 1h | — |
| T-006-02 | [OPS] | (Optionnel) Configurer GitHub label auto `size:XS/S/M/L/XL` via `pull-request-size` action | 1h | T-006-01 |

### Definition of Done

- [ ] CONTRIBUTING.md référence la règle
- [ ] Ressuscitée à la prochaine PR sprint-003 dépassant 400 lignes
