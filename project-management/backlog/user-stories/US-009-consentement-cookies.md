# US-009 — Consentement cookies

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from `CookieConsent` entity.

- **Implements**: FR-IAM-09
- **Persona**: P-007 + tous authentifiés
- **Estimate**: 3 pts
- **MoSCoW**: Must (RGPD/CNIL)

### Card
**As** visiteur ou utilisateur
**I want** accepter/refuser les cookies non essentiels
**So that** je contrôle ce qui me trace.

### Acceptance Criteria
```
Given visiteur première visite
When charge la page d'accueil
Then bandeau "accepter / refuser / paramétrer"
```
```
When refuse cookies non-essentiels
Then aucun script analytics chargé
And entrée CookieConsent persistée
```
```
When change d'avis depuis pied de page
Then préférences mises à jour
```

---
