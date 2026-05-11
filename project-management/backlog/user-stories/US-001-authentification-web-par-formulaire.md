# US-001 — Authentification web par formulaire

> **BC**: IAM  |  **Source**: archived IAM.md (split 2026-05-11)

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

