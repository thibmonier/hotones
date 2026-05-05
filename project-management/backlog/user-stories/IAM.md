# Module: Identity, Access & Tenancy (IAM)

> **DRAFT** — stories `INFERRED` from codebase. Validation required.
> Source: `project-management/prd.md` §5.1 (FR-IAM-01..FR-IAM-09)
> Generated: 2026-05-04

---

## US-001 — Authentification web par formulaire

> INFERRED from `SecurityController` + `security.yaml` form_login.

- **Implements**: FR-IAM-01
- **Module**: IAM
- **Source**: `src/Controller/SecurityController.php`, `config/packages/security.yaml`
- **Persona**: P-001..P-006 (tout utilisateur authentifié)
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** utilisateur d'une société (intervenant, chef de projet, manager, compta, admin)
**I want** me connecter à HotOnes via un formulaire email + mot de passe
**So that** j'accède à mon espace de travail isolé sur ma société.

### 3C
- [ ] Carte: validée
- [ ] Conversation: format mot de passe (politique), durée session, "remember me"
- [ ] Confirmation: tests fonctionnels login

### INVEST
- [ ] Independent — dépend de FR-IAM-05 multi-tenant
- [x] Negotiable / Valuable / Estimable / Sized / Testable

### Acceptance Criteria
**Scenario nominal — login OK**
```
Given utilisateur enregistré "intervenant@test.com" / "password" sur société Acme
When il soumet formulaire /login avec ces identifiants
Then il est redirigé vers /
And session établie avec ROLE_INTERVENANT
```

**Scenario erreur — mauvais mot de passe**
```
Given utilisateur "intervenant@test.com"
When il soumet /login avec mot de passe incorrect
Then redirigé sur /login avec message d'erreur générique
And aucune session créée
```

**Scenario erreur — utilisateur inconnu**
```
When email inexistant soumis
Then même message d'erreur générique (anti-énumération)
```

### Technical Notes
- Authenticator Symfony Security
- CSRF token requis (`enable_csrf: true`)
- Rate limiting attendu sur /login (à vérifier)

---

## US-002 — Authentification API JWT

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

## US-003 — Activation 2FA TOTP

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

## US-004 — Hiérarchie de rôles à 7 niveaux

> INFERRED from `security.yaml` role_hierarchy.

- **Implements**: FR-IAM-04
- **Source**: `config/packages/security.yaml`
- **Persona**: tous
- **Estimate**: 2 pts (couvert; surface = vérification)
- **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** propager les rôles via une hiérarchie SUPERADMIN → ADMIN → MANAGER → CHEF_PROJET → INTERVENANT → USER (avec branche COMPTA)
**So that** chaque permission est héritée sans duplication.

### Acceptance Criteria
```
Given user with ROLE_MANAGER
Then user has ROLE_CHEF_PROJET, ROLE_INTERVENANT, ROLE_USER
```
```
Given user with ROLE_COMPTA
Then user has ROLE_MANAGER and below (héritage simplifié documenté en yaml)
```
```
Given ROLE_COMMERCIAL referenced in code
Then ⚠️ vérifier: rôle absent de role_hierarchy → DECISION REQUIRED (R-02)
```

### Technical Notes
- ⚠️ R-02: `ROLE_COMMERCIAL` orphelin
- Couverture voters faible (R-01) → s'appuie sur access_control + IsGranted

---

## US-005 — Isolation multi-tenant par Company

> INFERRED from README + `Company` entity + `BusinessUnit` + multi-tenant claim.

- **Implements**: FR-IAM-05
- **Source**: `Company`, `CompanySettings`, `BusinessUnit`
- **Persona**: tous
- **Estimate**: 8 pts (audit + tests régression)
- **MoSCoW**: Must
- **⚠️ Risk**: R-03 — mécanisme de filtre tenant non localisé au scan

### Card
**As** utilisateur d'une société Acme
**I want** que toutes mes requêtes soient automatiquement filtrées sur ma société
**So that** je ne peux jamais lire ni modifier les données de la société Concurrent.

### Acceptance Criteria
```
Given user Alice de société Acme
And user Bob de société Concurrent
When Alice fait GET /clients
Then ne voit aucun client de Concurrent
```
```
Given Alice tente GET /clients/{id-de-Concurrent}
Then 404 (pas 403, pour anti-énumération)
```
```
Given un seed cross-tenant directement en BDD
When Alice fait toute requête sur ce ressource
Then absent du résultat
```

### Technical Notes
- À implémenter via Doctrine SQLFilter + TenantContext (cf. `.claude/rules/14-multitenant.md`)
- Tests d'isolation obligatoires
- Couvre toutes les entités tenant-scoped (~50 sur 63)

---

## US-006 — Gestion de profil utilisateur

> INFERRED from `ProfileController`, `AvatarController`, `EmploymentPeriodController`.

- **Implements**: FR-IAM-06
- **Persona**: P-001..P-005
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** utilisateur authentifié
**I want** consulter et modifier mon profil (info, avatar, période d'emploi)
**So that** mes données restent à jour et reconnaissables.

### Acceptance Criteria
```
Given user authentifié
When GET /profile
Then voit ses données + formulaire de modification
```
```
When upload avatar PNG/JPG ≤ N MB
Then stocké S3, exposé via /avatars/{slug}
```
```
When upload fichier non-image ou >N MB
Then rejet avec message clair
```

### Technical Notes
- Stockage S3 via flysystem
- Image pipeline via liip/imagine
- API personnelle: `/api/profile/`

---

## US-007 — Administration des utilisateurs

> INFERRED from `AdminUserController`.

- **Implements**: FR-IAM-07
- **Persona**: P-005, P-006
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** admin (tenant) ou superadmin
**I want** créer, modifier, désactiver des utilisateurs et leur attribuer des rôles
**So that** je gère les accès de ma société.

### Acceptance Criteria
```
Given admin authentifié
When POST /admin/users avec email + rôles
Then user créé dans la société de l'admin (multi-tenant)
And email d'invitation envoyé
```
```
Given admin tente d'attribuer ROLE_SUPERADMIN
Then refusé (uniquement superadmin peut)
```
```
When admin désactive un user
Then user ne peut plus se connecter; sessions invalidées
```

### Technical Notes
- ⚠️ Voter manquant pour autorisation fine (R-01)
- Audit log à vérifier (Blameable / Loggable)

---

## US-008 — Demande de suppression de compte (RGPD)

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

## US-009 — Consentement cookies

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

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-001 | Auth web formulaire | FR-IAM-01 | 3 | Must |
| US-002 | Auth API JWT | FR-IAM-02 | 3 | Must |
| US-003 | Activation 2FA TOTP | FR-IAM-03 | 5 | Should |
| US-004 | Hiérarchie rôles 7 niveaux | FR-IAM-04 | 2 | Must |
| US-005 | Isolation multi-tenant | FR-IAM-05 | 8 | Must |
| US-006 | Gestion profil | FR-IAM-06 | 3 | Must |
| US-007 | Admin utilisateurs | FR-IAM-07 | 5 | Must |
| US-008 | Demande suppression RGPD | FR-IAM-08 | 5 | Must |
| US-009 | Consentement cookies | FR-IAM-09 | 3 | Must |
| **Total** | | | **37** | |
