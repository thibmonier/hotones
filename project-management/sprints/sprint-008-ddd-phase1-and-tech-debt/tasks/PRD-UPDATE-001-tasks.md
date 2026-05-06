# PRD-UPDATE-001 — Tasks

> Atelier business reflect dans PRD (1 pt). 2 tasks / ~3h.

## Story rappel

Atelier business sprint-007 a produit 13 décisions Q1-Q13. PR #99 a déjà partiellement intégré les insights mais le PRD doit refléter explicitement:
- **FR-OPS-08** (nouvelle FR exigence)
- **Fusion FR-MKT-03 + FR-CRM-03** (anciennes 2 FRs deviennent 1)
- **ROLE_COMMERCIAL** decision (Option B: rôle séparé entre intervenant et manager)

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-PRD1-01 | [DOC] | Lire `project-management/prd.md` actuel + retrouver les décisions atelier business depuis backlog v2 (PR #100). Identifier exactement les sections à modifier | 1h | - | 🔲 |
| T-PRD1-02 | [DOC] | Modifier prd.md: 1) ajouter FR-OPS-08, 2) fusionner FR-MKT-03+FR-CRM-03 → FR-MKT-03 unifié, 3) section "Roles & Permissions" reflète ROLE_COMMERCIAL avec hiérarchie wiring sprint-006 | 2h | T-PRD1-01 | 🔲 |

## Acceptance Criteria

- [ ] FR-OPS-08 documentée dans PRD avec acceptance criteria
- [ ] FR-MKT-03 fusion explicite (référence FR-CRM-03 marquée DEPRECATED → MERGED INTO FR-MKT-03)
- [ ] Section Roles & Permissions PRD aligne avec config/packages/security.yaml (ROLE_COMMERCIAL hierarchy)
- [ ] Changelog PRD mis à jour avec entry "v1.1 — atelier business sprint-007 reflect"

## Sortie

Branche: `docs/prd-update-001-atelier-reflect`. PR base main.
