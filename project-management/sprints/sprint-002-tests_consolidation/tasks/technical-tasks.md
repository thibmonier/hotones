# Tâches Techniques Transverses — Sprint 001

## OPS-001 : Activer coverage PHPUnit + CI

**Type :** [OPS]
**Story Points :** 2
**Estimation :** 4.5h
**Dépend de :** -
**Priorité :** 🔴 Must (fondation mesure)

### Tâches (4.5h)

| ID | Type | Tâche | Est. |
|---|---|---|---:|
| T-OPS001-01 | [OPS] | Activer coverage dans `phpunit.xml.dist` (remove disabled) | 1h |
| T-OPS001-02 | [OPS] | Installer `pcov` extension dans Dockerfile.dev | 1h |
| T-OPS001-03 | [OPS] | CI GitHub Actions : upload coverage vers SonarCloud | 1h |
| T-OPS001-04 | [DOC] | README section "Running coverage locally" | 0.5h |
| T-OPS001-05 | [OPS] | Baseline coverage report initiale + badge README | 1h |

### Détails

#### T-OPS001-01 — Activer coverage phpunit.xml.dist

- **Fichier :** `phpunit.xml.dist` + `phpunit.coverage.xml`

**Changement :**
```xml
<coverage>
    <report>
        <clover outputFile="var/coverage/clover.xml"/>
        <html outputDirectory="var/coverage/html"/>
        <text outputFile="php://stdout" showOnlySummary="true"/>
    </report>
</coverage>
<source>
    <include>
        <directory>src/</directory>
    </include>
    <exclude>
        <directory>src/Kernel.php</directory>
        <directory>src/DataFixtures</directory>
    </exclude>
</source>
```

**Critères :**
- [ ] Coverage générée localement : `./vendor/bin/phpunit --coverage-html var/coverage/html`
- [ ] Temps exécution suite < 2× temps avant

#### T-OPS001-02 — pcov dans Dockerfile.dev

- **Fichier :** `Dockerfile.dev`

**Changement :**
```dockerfile
RUN pecl install pcov && docker-php-ext-enable pcov
```

**Alternative :** xdebug en coverage mode (plus lent mais déjà présent).

**Critères :**
- [ ] `docker compose exec app php -m | grep pcov` → présent
- [ ] Coverage fonctionne dans container

#### T-OPS001-03 — CI upload SonarCloud

- **Fichier :** `.github/workflows/ci.yml` ou `sonarqube.yml`

**Étape :**
```yaml
- name: Run tests with coverage
  run: composer test -- --coverage-clover var/coverage/clover.xml

- name: SonarCloud scan
  uses: SonarSource/sonarcloud-github-action@master
  env:
    SONAR_TOKEN: ${{ secrets.SONAR_TOKEN }}
  with:
    args: >
      -Dsonar.php.coverage.reportPaths=var/coverage/clover.xml
```

**Critères :**
- [ ] CI passe avec coverage
- [ ] SonarCloud dashboard montre % couverture
- [ ] Quality gate SonarCloud vert

#### T-OPS001-04 — README coverage section

- **Fichier :** `README.md`

**Ajouter section :**
```markdown
### Coverage locale
composer test                              # tests sans coverage
composer test -- --coverage-html var/coverage/html
open var/coverage/html/index.html
```

#### T-OPS001-05 — Baseline + badge

**Critères :**
- [ ] Baseline mesurée et documentée dans `project-management/metrics/`
- [ ] Badge Sonar ajouté README (Coverage badge)
- [ ] Seuil quality gate SonarCloud configuré (80% cible, 15% actuel)

---

## Tâches Refactoring (optionnelles, si temps Sprint 1)

### T-TECH-REF-01 : Nettoyer scripts racine (optionnel)

- **Type :** [OPS] | **Est :** 2h | **Priorité :** 🔵 Info

**Action :**
- Déplacer `build-assets.sh`, `docker-build-assets.sh`, `docker-benchmark.sh` → `scripts/`
- Supprimer `add-constructors.py`, `apply-company-fixes.sh`, `fix-company-context.php`, `fix-prod-migrations.sh` (refactor one-shots appliqués)

**Critères :**
- [ ] Racine projet plus propre
- [ ] README mis à jour avec nouveaux paths
- [ ] `composer scripts` mis à jour

### T-TECH-REF-02 : Consolider 5 Dockerfiles (optionnel)

- **Type :** [OPS] | **Est :** 3h | **Priorité :** 🔵 Info

**Action :**
- Supprimer `Dockerfile.original`, `Dockerfile.simple`
- Merger `Dockerfile.optimized` dans `Dockerfile` prod
- Garder `Dockerfile.dev` distinct

**Critères :**
- [ ] 2 Dockerfiles restent (prod + dev)
- [ ] Build prod OK
- [ ] Build dev OK

---

## Tâches reportées Sprint 2+

### T-REPORT-01 : Aligner CLAUDE.md racine avec réalité

- **Type :** [DOC] | **Est :** 1h
- Remplacer "PostgreSQL" → "MariaDB 11.4"
- Clarifier stack Flutter (absent actuellement)
- Ajuster description PHPStan level

### T-REPORT-02 : Amender Tech Spec gestion erreurs (+4 pts gate)

- **Type :** [DOC] | **Est :** 1h
- Ajouter section §5.x Error Handling Strategy
- Exception hierarchy, retry policy, circuit breaker AI

### T-REPORT-03 : Remplir PRD §3 Goals/Metrics

- **Type :** [DOC] | **Est :** 2h (atelier PO)
- Débloque gate-prd (actuellement 65/100)

### T-REPORT-04 : Découper US-058, US-105, US-142

- **Type :** [DOC] | **Est :** 1h
- Débloque gate-backlog

### T-REPORT-05 : Fixer dates Sprint 1

- **Type :** [DOC] | **Est :** 0.5h
- Débloque gate-sprint (85% → 100%)

---

## DoD transverse Sprint 1

- [ ] OPS-001 validée en CI verte
- [ ] Coverage baseline mesurée et reportée
- [ ] SonarCloud quality gate actif
- [ ] Pas de régression pipeline existante
- [ ] Tâches optionnelles OK si temps, sinon reportées
