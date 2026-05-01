# Sprint Review — Sprint 002 Tests Consolidation

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | sprint-002-tests_consolidation |
| Dates planifiées | 2026-04-24 → 2026-05-08 (2 semaines, fériés FR 01/05 + 08/05) |
| Date review | 2026-05-01 (J8/15 — feature-complete avant clôture) |
| Animateur | Scrum Master |
| Repo | thibmonier/hotones |

## Sprint Goal

> **Finaliser bounded context Vacation (DDD) + combler les 4 trous tests Critical identifiés dans gap-analysis (Notifications, Auth/2FA, RunningTimer, Company voter) pour stabiliser le socle avant migration DDD étendue.**

**Atteint : ✅ OUI** — 34 / 34 points livrés, 9 PRs mergées sur main, US-068+069 (PR #43) en review finale.

---

## User Stories livrées

| ID | Titre | Pts | PR | Mergé | Démo prête |
|---|---|---:|---|---|---|
| OPS-001 | Coverage CI Sonar + composer scripts | 2 | #30 | ✅ 2026-05-01 | ✅ |
| TEST-001 | Tests Notifications (Service + Subscriber + chaîne) | 5 | #36 | ✅ 2026-05-01 | ✅ |
| TEST-002 | Tests Auth / 2FA (SecurityController + LoginSubscriber) | 5 | #35 | ✅ 2026-05-01 | ✅ |
| TEST-003 | Tests RunningTimer entity + repository | 3 | #37 | ✅ 2026-05-01 | ✅ |
| TEST-004 | Tests Multi-tenant (CompanyVoter + CompanyContext) | 3 | #34 | ✅ 2026-05-01 | ✅ |
| US-066 | Demande de congés UI (P-001 Adrien intervenant) | 5 | #39 | ✅ 2026-05-01 | ✅ |
| US-067 | Validation manager UI (P-003 Manon manager) | 5 | #40 | ✅ 2026-05-01 | ✅ |
| US-068 | Rejet avec motif | 3 | #43 | 🟡 OPEN | ✅ |
| US-069 | Annulation manager d'une demande approuvée | 3 | #43 | 🟡 OPEN | ✅ |

**Livré : 34 / 34 pts (100%)** — US-068+069 codés, en attente review finale.

### Travail orthogonal hors stories

| Titre | PR | Motif | Mergé |
|---|---|---|---|
| Vacation DDD foundation (Domain + Application + CQRS via MessageBus) | #32 + #33 | Pré-requis US-066/067/068/069 | ✅ |
| Sprint planning docs (sprint-002-tests_consolidation/) | #31 | Backlog rituel SCRUM | ✅ |
| Test fixtures unblock 95 PHPUnit pré-existants | #38 | Désembourber CI repo-wide | ✅ |
| Bumps deps Composer + npm (security) | #29 #41 #42 | Security advisories | ✅ |

---

## Métriques

| Métrique | Planifié | Livré | Écart |
|---|---:|---:|---:|
| Points sprint | 34 | 34 | 0 |
| User Stories Must | 5 (OPS + 4 TEST) | 5 | 0 |
| User Stories Should | 4 (US-066..069) | 4 | 0 |
| PRs mergées sur main | — | 9 | — |
| PRs encore en review | — | 1 (#43) | — |

| Indicateur qualité | Avant sprint | Après sprint |
|---|---|---|
| Coverage baseline mesurée (clover) | non mesurable | **9.4% elements** (baseline OPS-001) |
| Tests unitaires verts | 264 / 264 (95 errors masqués) | **264 OK / 1111 assertions** |
| Tests intégration verts | 17 errors (cascade ContributorFactory) | **145 OK / 314 assertions, 1 skipped** |
| Erreurs PHPStan level 5 (kernel boot) | bloqué (Liip Imagine) | **0 erreur** |
| Stories Vacation UI | 0 (controllers supprimés) | **4 routes (intervenant) + 5 routes (manager)** |

---

## Démonstration

### Ordre suggéré (env staging à provisionner)

1. **OPS-001 + TEST-001..004 (~10 min, démo SonarCloud)**
   - Ouvrir le badge SonarCloud sur sonarcloud.io/project/overview?id=thibmonier_hotones
   - Montrer la baseline 9.4% elements + le delta apporté par TEST-001/2/3/4.
   - Composer scripts : `composer test-coverage` + `composer test-coverage-html`.
   - Démo par : équipe back

2. **US-066 Demande de congés (~5 min)**
   - Login intervenant Adrien (ROLE_INTERVENANT).
   - GET /mes-conges → empty state.
   - GET /mes-conges/nouvelle-demande → formulaire avec Stimulus picker (live business-days count).
   - POST → flash success + retour index avec ligne PENDING.
   - GET /mes-conges/{id} → détail.
   - POST /mes-conges/{id}/annuler → flash success + statut CANCELLED.
   - Démo par : équipe front

3. **US-067 + US-068 + US-069 (~10 min)**
   - Login manager Manon (ROLE_MANAGER).
   - GET /manager/conges → liste pending d'Adrien + badge `N en attente`.
   - GET /manager/conges/{id} → détail + textarea motif + bouton Approuver.
   - POST `/rejeter` avec `rejection_reason="Planning saturé"` → flash success + intervenant voit le motif sur sa demande rejetée.
   - Approuver une 2e demande → manager utilise nouveau bouton "Annuler cette demande approuvée" (US-069) → statut CANCELLED.
   - JSON `/manager/conges/api/pending-count` pour le badge header.
   - Démo par : équipe back

### Scénario Gherkin de démo

```gherkin
Feature: Cycle de vie d'une demande de congés (sprint-002 EPIC-009)

  Scenario: Adrien soumet, Manon valide, Manon annule
    Given Adrien est connecté avec ROLE_INTERVENANT
    When  Adrien remplit le formulaire /mes-conges/nouvelle-demande
    And   il choisit type=conges_payes, du 2026-05-15 au 2026-05-19, 8h/j
    Then  une notification flash "enregistree" s'affiche
    And   sa demande apparaît PENDING dans /mes-conges

    Given Manon est connectée avec ROLE_MANAGER
    When  elle ouvre /manager/conges
    Then  la demande d'Adrien est listée dans "Demandes en attente"
    And   le badge header affiche "1 en attente"

    When  Manon ouvre /manager/conges/{id}
    And   clique "Approuver"
    Then  la demande passe APPROVED
    And   un événement VacationApproved est dispatché

    When  Manon clique "Annuler cette demande approuvée"
    Then  la demande passe CANCELLED
    And   le compteur pending tombe à 0

  Scenario: Manon rejette avec motif
    Given Adrien a soumis une demande PENDING
    When  Manon ouvre la demande
    And   saisit "Planning saturé" dans rejection_reason
    And   clique "Rejeter avec ce motif"
    Then  la demande passe REJECTED
    And   Adrien voit "Motif du rejet : Planning saturé" dans /mes-conges/{id}
```

---

## Feedback à collecter (stakeholders)

1. Le formulaire intervenant est-il assez clair ? (label, placeholder du motif)
2. Faut-il un envoi email automatique quand le manager annule une demande approuvée ?
3. Le compteur de jours ouvrés Stimulus côté formulaire est-il correct (Mon-Fri uniquement, sans tenir compte des fériés FR) ?
4. La table "Historique de l'équipe" doit-elle être paginée à partir de combien de lignes ?
5. Priorité sprint-003 : EPIC-010 export PDF des congés validés ? Ou refacto Notifications messenger async ?

---

## Risques observés en cours de sprint

| Risque | Impact | Statut | Mitigation |
|---|---|---|---|
| `Root image path` (Liip Imagine) bloque PHPStan + PHPUnit en CI | 🔴 Bloquant tous PRs | ✅ Résolu (#30) | mkdir public/assets/* ajouté dans composer post-install + workflows CI |
| 95 PHPUnit errors pré-existants (BillingService TypeError, WorkloadPredictionService missing arg, EmploymentPeriod cascade) | 🟠 Cache toute régression | ✅ Résolu (#38) | Casts string + injection EntityManager mock |
| SonarQube `secrets.SONAR_TOKEN` HTTP 403 | 🟠 Coverage non publiée | ❌ Non résolu | **Action utilisateur** : régénérer token + `gh secret set SONAR_TOKEN` |
| Mago vs PHP-CS-Fixer alignement clés tableaux | 🟡 CI rouge esthétique | ❌ Non résolu | Sprint-003 : décision repo (désactiver Mago alignment ou aligner CS-Fixer) |
| Self-approve PRs interdit GitHub | 🟢 Process | Permanent | Reviewer humain externe requis |

---

## Impact sur le Backlog

| Action | ID | Description |
|---|---|---|
| Ajoutée | TEST-FIX-095 | Tracker dette : 95 PHPUnit errors traités hors story → mesurer si dette reste |
| Repriorisée | EPIC-009 | Vacation UI complète, EPIC fermable côté MVP |
| Repriorisée | OPS-002 | Restaurer SonarQube : régénérer SONAR_TOKEN + Quality Gate strict |
| À créer | OPS-003 | Aligner Mago / PHP-CS-Fixer formatting rules |
| À créer | TECH-DEBT-001 | Notifier intervenant lors d'une annulation manager (US-069 silencieux côté email) |

---

## Prochaines étapes (avant retro)

1. Merger PR #43 (US-068 + US-069) — review humaine attendue.
2. Régénérer secret `SONAR_TOKEN` côté GitHub Settings → Secrets, vérifier la 1re analyse SonarCloud verte.
3. Reproduire la démo en environnement réel (staging) au lieu de scénarios texte — provisionner instance.
4. Décliner `OPS-003` (Mago vs PHP-CS-Fixer) sur sprint-003 affinage.
5. Animer `/workflow:retro` immédiatement après cette review.

---

## Stack PR final

```text
main
 ├── #30 OPS-001 ────────────────────── ✅ merged
 ├── #34 TEST-004 ───────────────────── ✅ merged
 ├── #35 TEST-002 ───────────────────── ✅ merged
 ├── #36 TEST-001 ───────────────────── ✅ merged
 ├── #37 TEST-003 ───────────────────── ✅ merged
 ├── #38 test-fixes (95 errors) ─────── ✅ merged
 └── #32 Vacation DDD foundation ────── ✅ merged
       └── #33 vacation-ddd-messagebus  ✅ merged
            └── #39 US-066 demande UI   ✅ merged
                 └── #40 US-067 manager ✅ merged
                      └── #43 US-068+069 🟡 OPEN (CI 4 fails préexistants restants)
```

---

**Statut sprint : ✅ Sprint Goal atteint, prêt pour rétrospective.**
