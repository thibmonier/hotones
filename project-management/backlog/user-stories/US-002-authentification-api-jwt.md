# US-002 — Authentification API JWT

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from `lexik/jwt-authentication-bundle` + `/api/login` PUBLIC_ACCESS rule.

- **Implements**: FR-IAM-02
- **Source**: `lexik_jwt_authentication.yaml`, `security.yaml` access_control
- **Persona**: P-008 External integration
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** système externe ou client mobile
**I want** obtenir un JWT contre mes identifiants via `POST /api/login`
**So that** j'appelle les endpoints `/api/**` au nom d'un utilisateur tenant.

### Acceptance Criteria
```
Given identifiants valides
When POST /api/login {"username","password"}
Then 200 + {"token": "<JWT>"} signé RS256
And token contient claims user + roles + tenant
```
```
Given JWT expiré
When appel /api/<endpoint>
Then 401 token expired
```

### Technical Notes
- Clés RS256 générées en CI (`Generate JWT keys` step `ci.yml`)
- Expiration TTL à confirmer (config Lexik)
- Tenant claim à vérifier (cf. R-03 multi-tenant)

---

