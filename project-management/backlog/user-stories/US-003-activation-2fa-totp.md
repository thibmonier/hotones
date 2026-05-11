# US-003 — Activation 2FA TOTP

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

> INFERRED from `scheb/2fa-bundle` + `TwoFactorController` + `/2fa` PUBLIC_ACCESS path.

- **Implements**: FR-IAM-03
- **Source**: `src/Controller/TwoFactorController.php`, `scheb/2fa-totp`
- **Persona**: tout utilisateur authentifié
- **Estimate**: 5 pts
- **MoSCoW**: Should (Must pour ROLE_ADMIN+ selon politique)

### Card
**As** utilisateur authentifié
**I want** activer une 2FA TOTP (Google Authenticator) depuis mon profil
**So that** mon compte est protégé même si mon mot de passe fuite.

### Acceptance Criteria
```
Given utilisateur sans 2FA
When il scanne le QR code et confirme un code TOTP valide
Then 2FA activée
```
```
Given utilisateur avec 2FA active
When login OK + step-up TOTP
Then session pleine
```
```
Given code TOTP invalide
Then refusé, compteur d'échecs incrémenté
```

### Technical Notes
- QR via `endroid/qr-code-bundle`
- Form login intercepté par scheb avant session pleine

---

