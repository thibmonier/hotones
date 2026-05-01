# SonarQube / SonarCloud (OPS-002)

> Origine : sprint-003 OPS-002, retro sprint-002 action 3.

## Vue d'ensemble

Analyse statique + couverture publiées automatiquement sur **SonarCloud** à chaque push `main`/`develop` et à chaque PR. La baseline mesurée en sprint-002 (post-OPS-001) est de **9.4 % d'éléments couverts** ; cible long-terme **80 %**.

Configuration : `sonar-project.properties` à la racine.
Workflow : `.github/workflows/sonarqube.yml`.
Provider : [sonarcloud.io/project/overview?id=thibmonier_hotones](https://sonarcloud.io/project/overview?id=thibmonier_hotones).

## Badges (README.md)

```markdown
[![Quality Gate Status](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=alert_status)](...)
[![Coverage](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=coverage)](...)
[![Bugs](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=bugs)](...)
[![Vulnerabilities](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=vulnerabilities)](...)
[![Security Rating](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=security_rating)](...)
[![Maintainability Rating](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=sqale_rating)](...)
[![Code Smells](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=code_smells)](...)
[![Technical Debt](https://sonarcloud.io/api/project_badges/measure?project=thibmonier_hotones&metric=sqale_index)](...)
```

## Pré-requis repo

| Élément | Localisation | État | Action si manquant |
|---|---|---|---|
| `SONAR_TOKEN` | GitHub Settings → Secrets | ✅ Configuré 2025-11-24 + régénéré sprint-003 | `gh secret set SONAR_TOKEN --body "<token>"` |
| `sonar-project.properties` | racine | ✅ Présent | — |
| Workflow `sonarqube.yml` | `.github/workflows/` | ✅ Présent | — |
| Quality Gate "Sonar Way" + custom | sonarcloud.io project settings | ⏳ À configurer | cf. section ci-dessous |

## Régénérer le token (procédure)

```bash
# 1. sonarcloud.io → mon profil → Security → Generate Token
#    (scope: Execute Analysis, expiration: 90 jours conseillé)
#
# 2. Mettre à jour le secret repo :
gh secret set SONAR_TOKEN --body "<NEW_TOKEN>"

# 3. Vérifier en déclenchant manuellement le workflow :
gh workflow run "SonarQube Analysis"
gh run watch
```

Symptôme d'un token cassé : le workflow loge `Failed to query JRE metadata: HTTP 403 Forbidden. Please check the property sonar.token or the environment variable SONAR_TOKEN.`

## Quality Gate

### Configuration recommandée (à appliquer côté sonarcloud.io)

> Path : Project → Project Settings → Quality Gate → Choose another Quality Gate

Dupliquer le gate "Sonar Way" et durcir les seuils suivants pour **New Code** :

| Condition | Seuil sprint-003 | Seuil cible (sprint-005+) |
|---|---|---|
| Coverage on New Code | ≥ 80 % | ≥ 80 % |
| Duplicated Lines on New Code | ≤ 3 % | ≤ 3 % |
| Maintainability Rating on New Code | A | A |
| Reliability Rating on New Code | A | A |
| Security Rating on New Code | A | A |
| Security Hotspots Reviewed | 100 % | 100 % |

Pour **Overall Code** (legacy) on garde les valeurs par défaut tant que la couverture globale est < 25 %. Re-évaluer après TEST-005.

### Activer le check comme required

Une fois le gate fixé :

```bash
gh api -X PUT repos/thibmonier/hotones/branches/main/protection \
  -f required_status_checks='{"strict":true,"contexts":["SonarQube Scan","SonarCloud Code Analysis"]}'
```

Adapter le nom du status check au libellé exact qui apparaît sur la page PR.

## Workflow CI

Le job `SonarQube Scan` (`.github/workflows/sonarqube.yml`) tourne :

1. Setup PHP 8.5 + extension `pcov`.
2. `composer install --optimize-autoloader`.
3. **Création des dossiers d'assets** (OPS-001) pour permettre au kernel Symfony de booter via `liip_imagine.yaml`.
4. `vendor/bin/phpunit --configuration phpunit.coverage.xml` (avec `continue-on-error: true` tant que TEST-005 n'a pas relevé la baseline ; à supprimer une fois la suite phpunit verte sur main).
5. Vérification que `var/coverage/clover.xml` existe.
6. Upload via `sonarsource/sonarqube-scan-action@master`.

## Mesurer la couverture en local

```bash
# Clover XML pour reproduire ce que SonarCloud reçoit
docker compose exec app composer test-coverage
# -> var/coverage/clover.xml

# Rapport HTML navigable
docker compose exec app composer test-coverage-html
# -> var/coverage/html/index.html

# Sortie console rapide
docker compose exec app composer test-coverage-text
```

Sans Docker (post-OPS-005) :

```bash
composer test-coverage
```

## Failure modes connus

| Erreur | Cause | Action |
|---|---|---|
| `HTTP 403` query JRE metadata | `SONAR_TOKEN` invalide ou expiré | Régénérer le token (cf. plus haut) |
| `clover.xml not found` | PHPUnit n'a pas écrit le fichier (path coverage step manquant) | Vérifier `phpunit.coverage.xml` `<coverage>` ouverte |
| `Project bind required` | Mismatch projectKey vs UI Sonar | Renommer le projet côté sonarcloud.io ou ajuster `sonar.projectKey` |
| Quality Gate jamais visible sur la PR | Pas de check required configuré | Configurer via gh API (cf. section "Activer le check") |

## Roadmap qualité couverture

| Sprint | Coverage cible | Mesure | PR de référence |
|---|---|---|---|
| sprint-002 | baseline mesurée | 9.4 % | OPS-001 #30 |
| sprint-003 | 25 % | TEST-005 | (en cours) |
| sprint-004 | 50 % | follow-up TEST-005 | TBD |
| sprint-005+ | 80 % | progressif | TBD |

## Références

- Workflow : [`.github/workflows/sonarqube.yml`](../../.github/workflows/sonarqube.yml)
- Config : [`sonar-project.properties`](../../sonar-project.properties)
- Sprint-003 OPS-002 (this story)
- Retro sprint-002 action 3
