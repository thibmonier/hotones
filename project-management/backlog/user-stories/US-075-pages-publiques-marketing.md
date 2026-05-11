# US-075 — Pages publiques marketing

> **BC**: MKT  |  **Source**: archived MKT.md (split 2026-05-11)

> INFERRED from `PublicController`, `HomeController`, `AboutController` + access_control PUBLIC paths.

- **Implements**: FR-MKT-01 — **Persona**: P-007 — **Estimate**: 5 pts — **MoSCoW**: Should

### Card
**As** visiteur
**I want** consulter pages d'accueil, features, pricing, about, contact, légal
**So that** je découvre HotOnes avant inscription.

### Acceptance Criteria
```
When GET / /features /pricing /about /contact /legal
Then 200 sans authentification
```
```
Given visite tracée (analytics)
Then événement collecté (selon CookieConsent)
```

---

