# Sprint Review — Sprint 003 Stabilization (template)

> **Note** : ce document a été pré-rempli en J1 sprint-003 pendant la session de planification anticipée (2026-05-01). À finaliser le **2026-05-25** une fois les PRs mergées et la démo réalisée.

## Informations

| Attribut | Valeur |
|---|---|
| Sprint | sprint-003-stabilization |
| Dates planifiées | 2026-05-11 → 2026-05-25 (10 jours, fériés FR 14/05 + 15/05) |
| Date review prévue | 2026-05-25 14:00 |
| Animateur | Scrum Master |

## Sprint Goal

> **Stabiliser le pipeline CI/CD post-OPS-001, fermer la dette technique notifications du chemin Vacation, et provisionner un environnement staging démontrable pour les Sprint Reviews.**

**Atteint : à valider en review** — voir tableau ci-dessous.

## User Stories — état d'entrée en review

| ID | Titre | Pts | PR | Statut code | Mergé sur main |
|---|---|---:|---|---|---|
| OPS-002 | Restaurer SonarQube + Quality Gate | 3 | #53 | 🟢 livré | ⏳ |
| OPS-003 | ADR Mago vs PHP-CS-Fixer | 2 | #50 | 🟢 livré | ⏳ |
| OPS-004 | Monitoring CI main | 3 | #48 | 🟢 livré | ⏳ |
| OPS-005 | Hooks fallback no-Docker | 2 | #49 | 🟢 livré | ⏳ |
| OPS-006 | CONTRIBUTING.md PR<400 | 1 | #47 | 🟢 livré | ⏳ |
| TECH-DEBT-001 | Notif annulation manager | 3 | #52 | 🟢 livré | ⏳ |
| TECH-DEBT-002 | Cleanup CI résiduels (cs-fixer repo-wide) | 3 | #54 | 🟢 livré (stack sur #50) | ⏳ |
| TEST-005 | Coverage 9.4% → 25% | 5 | #55 | 🟢 livré | ⏳ |
| US-070 | Provision env staging Render free | 5 | #56 | 🟢 livré | ⏳ |
| US-071 | Email transactionnel Vacation | 3 | #57 | 🟢 livré (stack sur #56) | ⏳ |

**30 / 30 pts livrés en code dès J1.** Le sprint-003 est de fait un sprint d'**intégration & déploiement** plus que de développement.

## Métriques attendues fin sprint

| Indicateur | Cible | À mesurer le 2026-05-25 |
|---|---|---|
| Vélocité réelle | 30 pts | _à remplir_ |
| Taux de complétion | 100% | _à remplir_ |
| Coverage SonarCloud | 25% | _à mesurer une fois OPS-002 + TEST-005 mergés et 1er run Sonar vert_ |
| Tests unit | ≥ 375 OK | _à mesurer_ |
| CI main verte en continu (OPS-004) | ≥ 95% du temps | _à mesurer après 1 semaine d'observation_ |
| Issues `ci-incident` ouvertes par OPS-004 | 0 (idéal) | _à compter_ |

## Démonstration prévue

> Pour la 1ère fois grâce à US-070, démo sur env réel : `https://hotones-staging.onrender.com`

### Ordre suggéré (~30 min total)

1. **OPS-001 + TEST-005 → SonarCloud** (~5 min)
   - Ouvrir le badge Quality Gate dans le README → vert post-merge
   - Coverage 9.4% → 25% (à confirmer)
   - Démo par : équipe back

2. **OPS-004 monitoring CI** (~5 min)
   - Casser intentionnellement un test sur une branche, push, observer l'issue auto ouverte
   - La fermer en pushant un fix
   - Démo par : @tech-lead

3. **TECH-DEBT-001 + US-071 emails Vacation** (~10 min)
   - Sur staging : login intervenant → demande conge → manager rejette avec motif → intervenant reçoit email Mailtrap
   - Manager cancel approved → intervenant reçoit email d'annulation
   - Inbox Mailtrap projetée
   - Démo par : équipe back

4. **OPS-005 hooks fallback no-Docker** (~5 min)
   - Un dev sans Docker daemon fait `git commit` → hook fallback local s'exécute
   - Démo par : @dev-back

5. **OPS-006 + ADR-0002 (OPS-003) + TECH-DEBT-002** (~5 min)
   - Lecture de la nouvelle section CONTRIBUTING.md
   - Lecture de l'ADR-0002 (Mago/CS-Fixer)
   - Diff `git diff -w` sur la migration repo-wide → ~50 lignes lisibles
   - Démo par : @scrum-master

## Feedback à collecter (stakeholders)

1. La démo sur staging répond-elle au manque identifié sprint-002 retro ?
2. Le free tier Render est-il suffisant ou faut-il dégainer le budget pour passer payant ?
3. Le bandeau "STAGING" sur l'UI est-il assez visible (cf. `STAGING_BANNER` env var) ?
4. Le motif de rejet email est-il bien rendu côté Mailtrap inbox ?
5. Priorité sprint-004 : EPIC fonctionnel (e-signature, e-invoicing) ou continuer dette technique (gap-analysis Critical résiduels) ?

## Risques observés en cours de sprint

| Risque | Statut | Note |
|---|---|---|
| 30 / 30 pts livrés en J1 = sur-estimation des stories | _à valider_ | Les stories OPS-002, OPS-006 étaient effectivement plus rapides que prévu (1-3h vs 5-8h spec). À ajuster en planning poker sprint-004. |
| Stack PR (#54 sur #50, #57 sur #56) crée des dépendances de merge | _à valider_ | Ordre de merge documenté ; les CI rouges en cascade attendent la propagation. |
| 11 PRs ouvertes simultanément = goulot review humaine | 🔴 Confirmé J1 | Sera la matière de la rétro action. |

## Impact sur le Backlog (à compléter en review)

| Action | ID | Description |
|---|---|---|
| _à remplir_ | _à remplir_ | _à remplir_ |

## Prochaine étape

1. Animer la review le 2026-05-25 et remplir les sections "à mesurer" / "à remplir".
2. Lancer `/workflow:retro 003` immédiatement après.
3. Décider sprint-004 focus en priorisation collective.
