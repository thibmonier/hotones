# US-008 — Demande de suppression de compte (RGPD)

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from `GdprController` + `AccountDeletionRequest` entity.

- **Implements**: FR-IAM-08
- **Persona**: P-001..P-005
- **Estimate**: 5 pts
- **MoSCoW**: Must (RGPD)

### Card
**As** utilisateur HotOnes
**I want** demander la suppression de mes données personnelles
**So that** j'exerce mon droit à l'effacement (Art. 17 RGPD).

### Acceptance Criteria
```
Given user authentifié
When POST /gdpr/account-deletion
Then AccountDeletionRequest créée, statut "pending"
And email de confirmation envoyé
```
```
Given délai légal écoulé sans annulation
When job batch s'exécute
Then user anonymisé / supprimé selon politique
And données comptables conservées (durée légale)
```
```
Given utilisateur en cours de réservation/projet actif
When demande la suppression
Then bloquée avec explication (cf. dépendances RGPD)
```

### Technical Notes
- Anonymisation préférée à suppression dure (traçabilité comptable)
- Données médicales/RH chiffrées si applicable

---

