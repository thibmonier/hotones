# Tâches — Cluster Produit + Tech-Debt (sprint-003)

## US-070 — Provision env staging (5 pts)

**Persona :** P-004 PO + Stakeholders sprint-review
**Origine :** sprint-002 review action / retro thème D
**Dépend de :** budget hosting validé

### Tâches (12h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-070-01 | [OPS] | Provisionner instance Render (PHP 8.5 + PostgreSQL + Redis) ou alternative Fly.io | 3h | budget |
| T-070-02 | [OPS] | Configurer secrets staging (DATABASE_URL, REDIS_URL, JWT keys, SONAR_TOKEN, SENTRY_DSN) | 2h | T-070-01 |
| T-070-03 | [OPS] | Wirer auto-deploy sur push `main` via GitHub Actions | 2h | T-070-01 |
| T-070-04 | [DEV] | Charger fixtures démo : 1 Company "Demo Co", 3 Users (admin/manager/intervenant), 2 Projects, 5 Vacations états variés | 3h | T-070-02 |
| T-070-05 | [DOC] | `docs/05-deployment/staging.md` : URL stable, comptes démo, smoke test | 1h | T-070-04 |
| T-070-06 | [TEST] | Smoke test E2E sur staging via Panther : login + GET /mes-conges + soumission demande | 1h | T-070-04 |

### Definition of Done

- [ ] URL staging stable (e.g. `https://staging.hotones.app`)
- [ ] Smoke test vert
- [ ] Comptes démo connectables
- [ ] Auto-deploy déclenché au merge sur `main`

---

## TECH-DEBT-001 — Notifier intervenant lors d'une annulation manager (3 pts)

**Persona :** P-001 Adrien (intervenant)
**Origine :** sprint-002 retro impact + sprint-002 backlog impact (US-069 silencieux)

### Tâches (7h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-D001-01 | [BE] | `App\Application\Vacation\Notification\Message\VacationNotificationMessage` accepte type `cancelled-by-manager` (vs `cancelled` actuel) | 1h | — |
| T-D001-02 | [BE] | `VacationNotificationHandler` route le type vers une notif `NotificationType::VACATION_CANCELLED_BY_MANAGER` (nouvelle valeur enum) | 2h | T-D001-01 |
| T-D001-03 | [BE] | `NotificationType` enum : ajouter case + label/icon/color + mettre à jour `tests/Unit/Enum/NotificationTypeTest.php` (`exposes_exactly_11_notification_types`) | 1h | T-D001-02 |
| T-D001-04 | [TEST] | Functional test : POST /manager/conges/{id}/annuler → assertion notification persistée pour intervenant | 2h | T-D001-03 |
| T-D001-05 | [DOC] | Mettre à jour `docs/03-features/notifications.md` (section "Types de notifications", passe à 11 types) | 1h | T-D001-03 |

---

## TECH-DEBT-002 — Audit + cleanup CI checks résiduels (3 pts)

**Persona :** P-002 Responsable qualité
**Origine :** sprint-002 retro thème A — 4 checks rouges sur PR #43 (Mago, PHPCS, PHPUnit pré-existants résiduels, SonarQube)

### Tâches (8h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-D002-01 | [DEV] | Audit `composer phpcs` : extraire la liste exhaustive des erreurs repo-wide ; comparer avec sprint-002 baseline | 2h | OPS-003 |
| T-D002-02 | [DEV] | Auto-fix par `composer phpcbf` ; revue manuelle des erreurs restantes ; commit batch <400 lignes | 3h | T-D002-01 |
| T-D002-03 | [DEV] | Audit Mago restant après OPS-003 : tracker dans `mago.toml ignore` les patterns acceptés | 1h | OPS-003 |
| T-D002-04 | [TEST] | PHPUnit functional sur `main` : exécuter avec `ext-redis` chargé (CI réelle), confirmer 0 erreur résiduelle | 2h | OPS-002 |

### Definition of Done

- [ ] CI workflow `Quality` 100% vert sur dernière PR sprint-003
- [ ] CI workflow `CI` 100% vert (hors checks pre-existing acceptés explicitement)

---

## TEST-005 — Coverage 9.4% → 25% (5 pts)

**Persona :** P-002 Responsable qualité
**Origine :** OPS-001 baseline + roadmap qualité

### Tâches (12h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-005-01 | [TEST] | `App\Application\Vacation\Query\*Handler` — couvrir CountApprovedDays + GetContributorVacations + GetPendingVacationsForManager (3 handlers, 6-9 cas) | 3h | OPS-002 |
| T-005-02 | [TEST] | `App\Application\Vacation\Notification\VacationNotificationHandler` — couvrir mappings event→type | 2h | OPS-002 |
| T-005-03 | [TEST] | `App\Domain\Vacation\ValueObject\DateRange` + `DailyHours` + `VacationType` — élargir cas d'égalité, edge cases (date passée, leap year, weekend-only) | 3h | — |
| T-005-04 | [TEST] | `App\Service\NotificationService::cleanupOldNotifications` integration test sur DB réelle | 2h | OPS-002 |
| T-005-05 | [TEST] | Mesurer delta coverage avant/après via Sonar : objectif baseline mentionné dans T-005-00 doit passer 9.4% → 25% sur `App\Domain\Vacation\*` + `App\Application\Vacation\*` + `App\Service\NotificationService` | 2h | OPS-002 |

### Definition of Done

- [ ] Coverage Sonar global ≥ 12% (mesure macro)
- [ ] Coverage `App\Domain\Vacation\*` ≥ 70%
- [ ] Coverage `App\Application\Vacation\*` ≥ 60%
- [ ] Coverage `App\Service\NotificationService` ≥ 80%

---

## US-071 — Email transactionnel Vacation (3 pts, Could)

**Persona :** P-001 Adrien + P-003 Manon
**Origine :** TECH-DEBT-001 amplifié

### Tâches (8h)

| ID | Type | Tâche | Est. | Dépend |
|---|---|---|---:|---|
| T-071-01 | [BE] | Symfony Messenger handler async sur `VacationNotificationMessage` qui envoie un email via `Symfony\Mailer` | 2h | TECH-DEBT-001 |
| T-071-02 | [DEV] | Templates Twig email (`templates/emails/vacation/{requested,approved,rejected,cancelled,cancelled-by-manager}.html.twig`) | 2h | T-071-01 |
| T-071-03 | [OPS] | Configurer MAILER_DSN sur staging (Mailtrap dev) + production (Sendgrid free tier) | 1h | US-070 |
| T-071-04 | [TEST] | Functional : MailerAssertionsTrait pour vérifier `assertEmailCount(1)` sur chaque transition | 2h | T-071-02 |
| T-071-05 | [DOC] | `docs/03-features/notifications.md` — section "Emails transactionnels Vacation" | 1h | T-071-04 |

### Definition of Done

- [ ] 5 templates email rendus correctement
- [ ] Mailer dispatché async via Messenger
- [ ] Tests fonctionnels asserttent l'envoi
- [ ] Sur staging, l'envoi atterrit sur Mailtrap inbox visible

> **Could-have** : descope si OPS-002/003/004 ou US-070 dérapent. Reportable sprint-004.
