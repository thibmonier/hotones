# Tâches — TEST-FUNCTIONAL-FIXES-002

## Informations

- **Story Points** : 5
- **MoSCoW** : Must
- **Nature** : test
- **Origine** : sprint-005 retro (14 classes encore `skip-pre-push`)
- **Total estimé** : 7h

## Résumé

14 classes restent décorées `#[Group('skip-pre-push')]`. Sprint-005 (PR #84) a ajouté ce marker pour stabiliser le pre-push hook ; reste l'audit cas par cas. Trois résultats possibles :

1. **Fix** — root cause identifiée et corrigée → marker retiré.
2. **ADR-0003** — tolérance permanente documentée (test legacy reconnu instable mais utile en CI).
3. **Suppression** — test obsolète qui n'apporte plus de valeur.

## Liste actuelle (référence CONTRIBUTING.md)

| Classe | Catégorie | Hypothèse |
|---|---|---|
| `MultiTenant\ControllerAccessControlTest` | Multi-tenant filter | Probable ADR — filtre fait fail asserts cross-company. |
| `Controller\Analytics\DashboardControllerTest` | Session period | Fix possible via cookie persistance test. |
| `Controller\HomeControllerTest` | Auth flow flaky | Fix : audit guard auth en test env. |
| `Service\NotificationEventChainTest` | Event dispatch async | ADR probable — non-déterministe en container. |
| `Controller\OnboardingControllerTest` | Session/CSRF | Fix : pattern `csrfTokenFromForm()` (sprint-005). |
| `Controller\Admin\OnboardingTemplateControllerTest` | Admin EA5 fixtures | Fix : ajouter fixtures admin. |
| `Controller\OrderControllerPreviewTest` | Choice values mismatch | Fix probable — choices() inverted (cf. sprint-005 VacationType). |
| `Controller\PerformanceReviewControllerTest` | Session | Fix : pattern Onboarding. |
| `Controller\ProjectControllerFilterTest` | Query string redirect | Fix probable. |
| `Repository\RunningTimerRepositoryTest` | Inverse-side Collection | Fix : `addX()` côté inverse (pattern sprint-005). |
| `Controller\TimesheetControllerTest` | Multi-tenant | ADR probable. |
| Vacation tests (3) | DDD migration | ✅ Déjà fixés sprint-005 PR #82, marker neutre — à retirer. |

**Score visé** : 5 fix réussis + 3 vacation cleanup + 5 ADR ≈ 8 markers retirés / 6 ADR'd, soit 6 classes restantes documentées en ADR-0003.

## Vue d'ensemble

| ID | Type | Tâche | Estimation | Dépend de | Statut |
|---|---|---|---:|---|---|
| T-TFF2-01 | [TEST] | Retirer marker des 3 Vacation tests (déjà fixés en #82) + vérifier pre-push vert | 0.5h | - | 🔲 |
| T-TFF2-02 | [TEST] | Lot fix #1 : OnboardingController + PerformanceReview (pattern session/CSRF de sprint-005) | 2h | T-TFF2-01 | 🔲 |
| T-TFF2-03 | [TEST] | Lot fix #2 : RunningTimerRepository + OrderControllerPreview + ProjectControllerFilter | 2h | T-TFF2-01 | 🔲 |
| T-TFF2-04 | [DOC] | ADR-0003 "tolérance test legacy permanente" pour les 5 survivants probables | 1.5h | T-TFF2-02, T-TFF2-03 | 🔲 |
| T-TFF2-05 | [DOC] | Mettre à jour `CONTRIBUTING.md` table skip-pre-push | 1h | T-TFF2-04 | 🔲 |

## Détail

### T-TFF2-01 — Vacation cleanup

```bash
# Vérifier que les Vacation tests passent sans le marker
grep -l skip-pre-push tests/Functional/Controller/Vacation/
# pour chaque : retirer #[Group('skip-pre-push')] + import si plus utilisé
# puis make test-functional sans --exclude-group=skip-pre-push
```

### T-TFF2-04 — ADR-0003

Fichier : `docs/02-architecture/adr/0003-tolerance-test-legacy-permanente.md`

Contenu : critères pour qu'un test soit officiellement légitime en `skip-pre-push` permanent (échec non-déterministe, dépendance externe, infra non-test) ; liste finale des classes acceptées ; review annuelle obligatoire.

### T-TFF2-05 — CONTRIBUTING update

Mettre à jour la table de la section "Pre-push baseline" pour refléter l'état post-sprint-006 + lien vers ADR-0003.

## DoD

- [ ] 3 Vacation markers retirés.
- [ ] ≥ 5 fix réussis (objectif 8).
- [ ] ADR-0003 créé.
- [ ] CONTRIBUTING.md table à jour.
- [ ] Pre-push hook reste vert avec moins d'exclusions.
- [ ] CI complète (incluant les groups) reste verte.
