# Sprint 001 : Taches Techniques Transverses

> Derniere mise a jour : 2026-02-22

---

## Resume

| Attribut | Valeur |
|----------|--------|
| **Nombre de taches** | 3 |
| **Estimation totale** | 3h |

---

## Taches

### T-TECH-01 : [OPS] Verifier config Turbo/Stimulus dans projet

| Attribut | Valeur |
|----------|--------|
| **ID** | T-TECH-01 |
| **Type** | [OPS] |
| **Estimation** | 1h |
| **Statut** | :black_square_button: A faire |
| **Depend de** | Aucune |
| **Bloque** | T-001-01, T-002-03 |

**Description :**

Verifier que Turbo et Stimulus sont correctement configures et fonctionnels dans le projet.

**Sous-taches :**
- [ ] Verifier que `@hotwired/turbo` et `@hotwired/stimulus` sont installes et a jour
- [ ] Verifier la configuration dans `importmap.php` ou `package.json`
- [ ] Verifier que `app.js` initialise correctement Stimulus et Turbo
- [ ] Tester qu'un Turbo Frame basique fonctionne (navigation sans rechargement)
- [ ] Tester qu'un Stimulus controller basique se connecte correctement

**Fichiers concernes :**
- `importmap.php` ou `package.json`
- `assets/app.js`
- `assets/bootstrap.js`

---

### T-TECH-02 : [REV] Code review US-001

| Attribut | Valeur |
|----------|--------|
| **ID** | T-TECH-02 |
| **Type** | [REV] |
| **Estimation** | 1h |
| **Statut** | :black_square_button: A faire |
| **Depend de** | T-001-05, T-001-06 |

**Description :**

Revue de code complete de l'US-001 avant merge.

**Checklist :**
- [ ] Architecture : SOLID respecte, pas de violation
- [ ] Code quality : KISS/DRY/YAGNI, nommage explicite
- [ ] Tests : couverture >= 80%, tests pertinents
- [ ] Securite : pas de donnees sensibles, validation inputs
- [ ] Performance : pas de N+1 queries, pagination correcte
- [ ] PHPStan : 0 erreur au niveau max
- [ ] CS Fixer : code formate

---

### T-TECH-03 : [REV] Code review US-002

| Attribut | Valeur |
|----------|--------|
| **ID** | T-TECH-03 |
| **Type** | [REV] |
| **Estimation** | 1h |
| **Statut** | :black_square_button: A faire |
| **Depend de** | T-002-05, T-002-06 |

**Description :**

Revue de code complete de l'US-002 avant merge.

**Checklist :**
- [ ] Architecture : SOLID respecte, pas de violation
- [ ] Code quality : KISS/DRY/YAGNI, nommage explicite
- [ ] Tests : couverture >= 80%, tests pertinents
- [ ] Securite : verification acces devis, pas de donnees sensibles dans le PDF
- [ ] Performance : generation PDF acceptable (< 3s)
- [ ] PHPStan : 0 erreur au niveau max
- [ ] CS Fixer : code formate
