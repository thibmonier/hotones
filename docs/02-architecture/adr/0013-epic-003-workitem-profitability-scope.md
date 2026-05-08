# ADR-0013 — EPIC-003 : WorkItem & Profitability — scope + stack + KPIs

| Champ | Valeur |
|---|---|
| Statut | ✅ Accepté |
| Date | 2026-05-08 |
| Sprint | sprint-019-epic-003-scoping |
| Story | EPIC-003-KICKOFF-WORKSHOP |
| Auteur | Tech Lead + PO (atelier sprint-019 J1) |

---

## Contexte

EPIC-002 (Observabilité & Performance) clôturé sprints 016-018 (3 sprints,
~33 pts livrés, vélocité 124-130 % engagement ferme deux sprints consécutifs).

Sprint-019 démarre EPIC-003. Atelier PO sprint-019 J1 a tranché les 5 questions
ouvertes :

| # | Question | Réponse PO |
|---|---|---|
| 1 | Scope EPIC-003 (BC business) | **B — WorkItem & Profitability** (cœur métier agence) |
| 2 | MVP minimum 2-3 stories | **calcul marge projet temps réel** + **alerte > seuil dépassement** |
| 3 | Budget complexité | **4-6 sprints** (timeline normale, espace dette) |
| 4 | Stack tech | **State machine Symfony** (Workflow component, pas d'engine externe) |
| 5 | Mesure succès KPIs | **3 nouveaux KPIs dashboard** : DSO + temps facturation + % projets avec marge calculée temps réel |

---

## Décision

**EPIC-003 = WorkItem & Profitability** — bounded context cœur métier agence.

### Scope MVP (2-3 stories prioritaires)

1. **Calcul marge projet temps réel** : `Project` aggregate calcule
   automatiquement coût (somme `WorkItem` × `hourlyRate` contributeur) vs
   facturé (somme `Invoice` payée), expose `getMarge()` + `getMargePercent()`.

2. **Alerte > seuil dépassement** : dès que `WorkItem` ajouté dépasse seuil
   marge configuré (ex `< 10 %`), `MarginThresholdExceededEvent` dispatché,
   notification Slack `#alerts-prod` via `SlackAlertingService` (US-094).

3. **Dashboard 3 KPIs étendus** :
   - **DSO** (Days Sales Outstanding) : délai paiement moyen factures émises
   - **Temps de facturation** : lead time devis signé → facture émise
   - **% projets avec marge calculée temps réel** : adoption MVP

### Stack technique

**State machine Symfony Workflow component** (déjà bundled framework).

```yaml
# config/packages/workflow.yaml — exemple WorkItem state machine
framework:
    workflows:
        work_item:
            type: state_machine
            marking_store:
                type: method
                property: status
            supports:
                - App\Domain\WorkItem\Entity\WorkItem
            initial_marking: draft
            places:
                - draft
                - validated
                - billed
                - paid
            transitions:
                validate:
                    from: draft
                    to: validated
                bill:
                    from: validated
                    to: billed
                mark_paid:
                    from: billed
                    to: paid
```

Pas de workflow engine externe (Camunda Zeebe / Temporal). Justifications :
- Workflows linéaires simples (3-5 états)
- Pas de long-running processes >24h
- Pas de retries complexes (Symfony Messenger suffit)
- Coût infra externe non justifié au stade traction
- Courbe apprentissage zéro (Workflow Symfony déjà connu équipe)

### Timeline cible

**4-6 sprints** (sprints 020-024 ou 025).

| Sprint | Scope |
|---|---|
| 020 | EPIC-003 Phase 1 — DDD WorkItem entity + ValueObjects + interfaces |
| 021 | EPIC-003 Phase 2 — ACL translators flat↔DDD + DoctrineWorkItemRepository |
| 022 | EPIC-003 Phase 3 — Workflow Symfony state machine + UC `CalculateProjectMargin` |
| 023 | EPIC-003 Phase 3+ — `MarginThresholdExceededEvent` + alerting Slack + dashboard 3 KPIs |
| 024 | EPIC-003 Phase 4 (optionnel) — mutations via UC + migration write |
| 025 | Buffer + polish + docs ADR-0014 retour d'expérience |

### Mesure succès

**3 KPIs nouveaux dashboard** (extension `BusinessKpiService`) :

| KPI | Calcul | Cible production |
|---|---|---|
| **DSO** (Days Sales Outstanding) | Sum (paid_at - issued_at) / count(paid invoices) | < 45 jours médiane |
| **Temps de facturation** | Sum (invoice.created_at - order.signed_at) / count | < 7 jours médiane |
| **% projets avec marge temps réel** | count(projects WHERE marge_calculee_at IS NOT NULL) / count(projects) | > 80 % adoption fin EPIC |

---

## Trigger réversibilité / abandon

EPIC-003 abandonné si :
1. **Trop complexe** (> 6 sprints sans MVP livré) → réduire scope MVP ou
   pivot EPIC-004
2. **Pas de traction PO** (< 3 utilisations / mois alerte dépassement marge
   après 1 mois prod) → fonctionnalité gadget, ré-évaluer
3. **Bug data integrity** (calcul marge faux > 5 % vs réalité comptable) →
   bloquer scaling + investiguer

---

## Alternatives écartées

### A — Time tracking (RunningTimer DDD)
**Écarté** : Time tracking actuel (flat) suffit pour mesurer prod. Migration
DDD pure n'apporte pas de valeur business immédiate. À planifier sprint-026+
en post-EPIC-003 si besoin.

### C — Invoicing automation
**Écarté** : Workflow devis → facture déjà semi-automatisé (`CreateInvoiceDraftUseCase`
existe sprint-017). Automation complète = nice-to-have, pas critique avant
mesure profitability (qui informe quoi facturer).

### D — Reporting analytics avancé
**Écarté** : Dashboard 7 KPIs sprint-017 + 3 nouveaux KPIs sprint-019 suffisent
au stade. Drill-down filtres = sprint-026+ si demande PO réelle.

### Stack — Workflow engine externe (Camunda / Temporal)
**Écarté** : Sur-engineering au stade. Coût infra ($) + courbe apprentissage
non justifiés pour workflows linéaires 3-5 états. Réévaluer si workflows BPMN
complexes > 10 états apparaissent.

---

## Conséquences

### Positives
- ✅ **Cœur métier agence renforcé** : profitability mesurée temps réel
- ✅ **Détection dérives projet précoce** : alertes avant fin projet (vs
  audit post-mortem actuel)
- ✅ **Pilotage PO data-driven** : 3 KPIs DSO/facturation/adoption visibles
- ✅ **Pattern strangler fig réutilisé** : 4ème BC après Client/Project/Order/Invoice
- ✅ **Stack stable** : pas de nouveau composant infra à maintenir

### Négatives
- ❌ **Complexité métier élevée** : marge = sum(coût × heures) - sum(facturé),
  edge cases (avenants, refacturations) à modéliser
- ❌ **Dépendance données existantes** : qualité `WorkItem.cost` actuel flat
  potentiellement irrégulière (à valider sprint-020 J1 audit)
- ❌ **Adoption requise** : % projets avec marge temps réel = adoption mesurable
  → si < 50 % à mi-parcours, alerter PO

---

## Liens

- ADR-0008 : Anti-Corruption Layer pattern (strangler fig)
- ADR-0009 : DDD Phase 3 controller migration pattern
- ADR-0011 : DDD foundation stabilized
- ADR-0012 : Stack observabilité (sprint-016)
- US-094 : `SlackAlertingService` (sera réutilisé alertes margin)
- US-093 : Dashboard 7 KPIs business prod (sera étendu 3 KPIs)
- Sprint-019 kickoff : `project-management/sprints/sprint-019-epic-003-scoping/sprint-goal.md`

---

**Date de dernière mise à jour :** 2026-05-08
**Version :** 1.0.0
