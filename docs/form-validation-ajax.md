# Validation AJAX des Formulaires

Ce document explique comment utiliser le système de validation AJAX temps réel implémenté dans le projet.

## Vue d'ensemble

Le système de validation AJAX permet de valider les champs de formulaire en temps réel (pendant la saisie) sans recharger la page. Cela améliore l'expérience utilisateur en donnant un feedback immédiat sur la validité des données saisies.

## Fichiers impliqués

- **JavaScript**: `assets/js/form-validation.js` - Module de validation côté client
- **Contrôleur**: `src/Controller/ValidationController.php` - Endpoint de validation côté serveur
- **Endpoint**: `POST /api/validate` - Route d'API pour validation

## Types de validation supportés

### Validation locale (côté client uniquement)

Ces validations sont effectuées instantanément sans appel serveur :

- `email` : Format email valide
- `siret` : 14 chiffres
- `phone` : Format français (+33 ou 0, 9 chiffres)
- `url` : URL valide
- `date` : Date parsable
- `number` : Nombre valide

### Validation AJAX (avec appel serveur)

Ces validations nécessitent un appel au serveur pour vérifier l'unicité ou des règles métier :

- `email_unique` : Email unique dans la table users
- `siret_unique` : SIRET unique dans la table clients
- `client_name_unique` : Nom de client unique

## Utilisation dans un formulaire

### Exemple simple : Validation format email

```html
<div class="mb-3">
    <label for="email" class="form-label">Email *</label>
    <input type="email"
           class="form-control"
           id="email"
           name="email"
           data-validation-url="/api/validate"
           data-validation-type="email"
           required>
</div>
```

### Exemple avancé : Validation unicité email

```html
<div class="mb-3">
    <label for="user_email" class="form-label">Email *</label>
    <input type="email"
           class="form-control"
           id="user_email"
           name="user[email]"
           data-validation-url="/api/validate"
           data-validation-type="email_unique"
           required>
</div>
```

### Exemple : Validation SIRET avec unicité

```html
<div class="mb-3">
    <label for="siret" class="form-label">SIRET</label>
    <input type="text"
           class="form-control"
           id="siret"
           name="client[siret]"
           data-validation-url="/api/validate"
           data-validation-type="siret_unique"
           placeholder="12345678901234"
           maxlength="14">
</div>
```

### Exemple : Formulaire complet avec validation à la soumission

```html
<form method="post" data-validate-on-submit>
    <div class="mb-3">
        <label for="client_name" class="form-label">Nom du client *</label>
        <input type="text"
               class="form-control"
               id="client_name"
               name="client[name]"
               data-validation-url="/api/validate"
               data-validation-type="client_name_unique"
               required>
    </div>

    <div class="mb-3">
        <label for="siret" class="form-label">SIRET</label>
        <input type="text"
               class="form-control"
               id="siret"
               name="client[siret]"
               data-validation-url="/api/validate"
               data-validation-type="siret_unique"
               maxlength="14">
    </div>

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input type="email"
               class="form-control"
               id="email"
               name="client[email]"
               data-validation-url="/api/validate"
               data-validation-type="email">
    </div>

    <button type="submit" class="btn btn-primary">Enregistrer</button>
</form>
```

## Attributs data-* requis

| Attribut | Description | Obligatoire |
|----------|-------------|-------------|
| `data-validation-url` | URL de l'endpoint de validation | Oui |
| `data-validation-type` | Type de validation à effectuer | Oui |

## Comportement

### Pendant la saisie (debounce)

- Le champ est validé **500ms après la dernière frappe**
- Un indicateur de chargement apparaît pendant la validation AJAX
- Le message de validation apparaît sous le champ

### À la perte de focus (blur)

- Le champ est validé **immédiatement** si une valeur est présente
- Annule le timer de debounce en cours

### À la soumission du formulaire

Si l'attribut `data-validate-on-submit` est présent sur le `<form>` :

- Le formulaire **ne peut pas être soumis** si des champs sont invalides
- Un message d'erreur global apparaît
- La page scroll automatiquement vers le premier champ invalide

## Classes CSS ajoutées automatiquement

- `.is-validating` : Pendant la validation AJAX (indicateur de chargement)
- `.is-valid` : Champ valide (bordure verte)
- `.is-invalid` : Champ invalide (bordure rouge)

## Messages de validation

Les messages sont affichés dans un élément `.validation-feedback` créé dynamiquement sous le champ :

```html
<div class="validation-feedback text-danger small mt-1">
    Ce SIRET est déjà utilisé par un autre client
</div>
```

ou

```html
<div class="validation-feedback text-success small mt-1">
    ✓ SIRET disponible
</div>
```

## Ajouter un nouveau type de validation

### 1. Validation côté client (JavaScript)

Modifier `assets/js/form-validation.js` dans la fonction `localValidation()` :

```javascript
case 'custom_type':
    if (!isValidCustom(value)) {
        showValidationMessage(field, 'Message d\'erreur personnalisé', 'error');
        return false;
    }
    break;
```

Puis ajouter la fonction de validation :

```javascript
function isValidCustom(value) {
    // Votre logique de validation
    return /^regex$/.test(value);
}
```

### 2. Validation côté serveur (PHP)

Modifier `src/Controller/ValidationController.php` dans la méthode `validate()` :

```php
return match ($type) {
    'email_unique'        => $this->validateEmailUnique($value, $excludeId),
    'siret_unique'        => $this->validateSiretUnique($value, $excludeId),
    'custom_validation'   => $this->validateCustom($value, $excludeId),  // ← Nouveau
    default               => new JsonResponse([
        'valid'   => false,
        'message' => 'Type de validation inconnu',
    ], Response::HTTP_BAD_REQUEST),
};
```

Puis ajouter la méthode de validation :

```php
private function validateCustom(string $value, ?int $excludeId): JsonResponse
{
    // Votre logique de validation

    if ($invalid) {
        return new JsonResponse([
            'valid'   => false,
            'message' => 'Message d\'erreur',
        ]);
    }

    return new JsonResponse([
        'valid'   => true,
        'message' => '✓ Valide',
    ]);
}
```

## Format de réponse API attendu

L'endpoint `/api/validate` doit toujours retourner un JSON avec ce format :

```json
{
    "valid": true,
    "message": "✓ Email disponible"
}
```

ou

```json
{
    "valid": false,
    "message": "Cet email est déjà utilisé"
}
```

## Exemple complet : Formulaire de création client

```twig
{% extends 'base.html.twig' %}

{% block body %}
<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Nouveau client</h5>

                {{ form_start(form, {'attr': {'data-validate-on-submit': true}}) }}

                <div class="mb-3">
                    {{ form_label(form.name) }}
                    {{ form_widget(form.name, {
                        'attr': {
                            'data-validation-url': path('api_validate'),
                            'data-validation-type': 'client_name_unique',
                            'class': 'form-control'
                        }
                    }) }}
                </div>

                <div class="mb-3">
                    {{ form_label(form.siret) }}
                    {{ form_widget(form.siret, {
                        'attr': {
                            'data-validation-url': path('api_validate'),
                            'data-validation-type': 'siret_unique',
                            'class': 'form-control',
                            'maxlength': 14
                        }
                    }) }}
                    <small class="form-text text-muted">14 chiffres sans espaces</small>
                </div>

                <div class="mb-3">
                    {{ form_label(form.email) }}
                    {{ form_widget(form.email, {
                        'attr': {
                            'data-validation-url': path('api_validate'),
                            'data-validation-type': 'email',
                            'class': 'form-control'
                        }
                    }) }}
                </div>

                <button type="submit" class="btn btn-primary">Enregistrer</button>
                <a href="{{ path('client_index') }}" class="btn btn-secondary">Annuler</a>

                {{ form_end(form) }}
            </div>
        </div>
    </div>
</div>
{% endblock %}
```

## Gestion de l'édition

Lors de l'édition d'une entité existante, il faut exclure l'ID de l'entité en cours de modification pour ne pas déclencher d'erreur d'unicité sur ses propres données.

**À implémenter côté serveur uniquement** : le JavaScript n'a pas besoin de connaître l'ID, c'est le contrôleur qui doit le récupérer du contexte.

## Performances

- **Debounce 500ms** : Évite trop de requêtes pendant la saisie
- **Cache navigateur** : Les réponses peuvent être cachées
- **Validation locale d'abord** : Évite des appels serveur inutiles

## Sécurité

- ✅ Protection CSRF non nécessaire (lecture seule)
- ✅ Authentification requise (`#[IsGranted('ROLE_USER')]`)
- ✅ Échappement HTML automatique dans le JavaScript
- ✅ Validation côté serveur **toujours** effectuée lors de la soumission du formulaire

## Limitations

- Maximum 1 validation AJAX en cours par champ
- Délai de 500ms avant validation (par design)
- Les champs vides ne sont pas validés sauf si `required`
- Compatible avec Bootstrap 5 uniquement (pour les classes `.is-valid` / `.is-invalid`)

## Dépannage

### La validation ne se déclenche pas

1. Vérifier que les attributs `data-validation-url` et `data-validation-type` sont présents
2. Vérifier que le fichier `form-validation.js` est bien chargé
3. Ouvrir la console du navigateur pour voir les erreurs

### Le message de validation n'apparaît pas

1. Vérifier que le champ est dans un élément parent (ex: `<div class="mb-3">`)
2. Vérifier la structure HTML du formulaire
3. Vérifier que l'API retourne bien un JSON valide

### Le formulaire se soumet malgré les erreurs

1. Vérifier que l'attribut `data-validate-on-submit` est présent sur le `<form>`
2. Vérifier que le JavaScript n'a pas d'erreurs dans la console

## Bonnes pratiques

1. **Toujours valider côté serveur également** : La validation JavaScript peut être contournée
2. **Messages clairs et concis** : "Email déjà utilisé" plutôt que "Erreur"
3. **Feedback positif** : Montrer ✓ quand c'est valide
4. **Pas trop de validations AJAX** : Privilégier la validation locale quand possible
5. **Tester l'UX** : Vérifier que le délai de 500ms est acceptable pour l'utilisateur
