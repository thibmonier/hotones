# Validation AJAX des Formulaires

## Vue d'ensemble

Le système de validation AJAX permet de valider les champs de formulaire en temps réel, avant la soumission. Les validations côté client sont complétées par des vérifications côté serveur pour garantir l'intégrité des données.

## Fonctionnalités

- ✅ Validation en temps réel (sur `blur` ou `input`)
- ✅ Debouncing pour éviter trop de requêtes
- ✅ Feedback visuel immédiat (classes Bootstrap `is-valid` / `is-invalid`)
- ✅ Messages d'erreur contextuels
- ✅ Validation côté serveur via API
- ✅ Indicateur de chargement pendant la validation
- ✅ Support de multiples types de validation

## Utilisation Basique

### 1. Inclure le Script

Ajoutez le script dans votre template Twig :

```twig
{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('assets/form-validation.js') }}"></script>
{% endblock %}
```

### 2. Ajouter les Attributs de Validation

Ajoutez `data-validation-url` et `data-validation-type` sur vos champs :

```twig
<div class="mb-3">
    <label for="client_email" class="form-label">Email</label>
    <input
        type="email"
        class="form-control"
        id="client_email"
        name="client[email]"
        data-validation-url="{{ path('api_validate') }}"
        data-validation-type="email"
        required
    >
</div>
```

### 3. Activer la Validation au Submit (Optionnel)

Pour empêcher la soumission si des champs sont invalides :

```twig
<form method="post" data-validate-on-submit>
    <!-- vos champs ici -->
</form>
```

## Types de Validation Disponibles

### Email

Valide le format d'une adresse email.

```html
<input
    type="email"
    name="email"
    data-validation-url="{{ path('api_validate') }}"
    data-validation-type="email"
>
```

### SIRET

Valide un numéro SIRET (14 chiffres) et vérifie l'unicité.

```html
<input
    type="text"
    name="siret"
    data-validation-url="{{ path('api_validate') }}"
    data-validation-type="siret"
>
```

### Téléphone

Valide un numéro de téléphone français.

```html
<input
    type="tel"
    name="phone"
    data-validation-url="{{ path('api_validate') }}"
    data-validation-type="phone"
>
```

### URL

Valide une URL complète.

```html
<input
    type="url"
    name="website"
    data-validation-url="{{ path('api_validate') }}"
    data-validation-type="url"
>
```

### Unicité (Nom de Client)

Vérifie qu'un nom de client n'existe pas déjà en base.

```html
<input
    type="text"
    name="client[name]"
    data-validation-url="{{ path('api_validate') }}"
    data-validation-type="client_name_unique"
>
```

## Validation sur Edit (Exclure l'ID Courant)

Lors de l'édition, passez l'ID de l'entité pour l'exclure de la vérification d'unicité :

```html
<input
    type="hidden"
    name="client[id]"
    value="{{ client.id }}"
>
<input
    type="text"
    name="client[name]"
    data-validation-url="{{ path('api_validate') }}"
    data-validation-type="client_name_unique"
>
```

Le JavaScript récupère automatiquement l'ID et l'envoie comme `exclude_id`.

## Exemple Complet : Formulaire Client

```twig
<form method="post" action="{{ path('client_new') }}" data-validate-on-submit>
    {{ form_start(form) }}

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="client_name" class="form-label">Nom du client *</label>
                <input
                    type="text"
                    class="form-control"
                    id="client_name"
                    name="client[name]"
                    data-validation-url="{{ path('api_validate') }}"
                    data-validation-type="client_name_unique"
                    required
                >
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="client_email" class="form-label">Email *</label>
                <input
                    type="email"
                    class="form-control"
                    id="client_email"
                    name="client[email]"
                    data-validation-url="{{ path('api_validate') }}"
                    data-validation-type="email"
                    required
                >
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="client_siret" class="form-label">SIRET</label>
                <input
                    type="text"
                    class="form-control"
                    id="client_siret"
                    name="client[siret]"
                    data-validation-url="{{ path('api_validate') }}"
                    data-validation-type="siret"
                    placeholder="12345678901234"
                >
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="client_phone" class="form-label">Téléphone</label>
                <input
                    type="tel"
                    class="form-control"
                    id="client_phone"
                    name="client[phone]"
                    data-validation-url="{{ path('api_validate') }}"
                    data-validation-type="phone"
                    placeholder="01 23 45 67 89"
                >
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">
        <i class="bx bx-save me-2"></i>Enregistrer
    </button>

    {{ form_end(form) }}
</form>
```

## Ajouter un Nouveau Type de Validation

### 1. Côté Serveur (Controller)

Ajoutez un cas dans le `match` du `ValidationController`:

```php
return match ($type) {
    'client_name_unique' => $this->validateClientNameUnique($value, $excludeId),
    'email'              => $this->validateEmail($value),
    'siret'              => $this->validateSiret($value, $excludeId),
    'phone'              => $this->validatePhone($value),
    'url'                => $this->validateUrl($value),
    'my_custom_type'     => $this->validateMyCustomType($value), // NOUVEAU
    default              => new JsonResponse([
        'valid'   => false,
        'message' => 'Type de validation inconnu',
    ], Response::HTTP_BAD_REQUEST),
};
```

Implémentez la méthode de validation :

```php
private function validateMyCustomType(string $value): JsonResponse
{
    // Votre logique de validation
    if (/* condition invalide */) {
        return new JsonResponse([
            'valid'   => false,
            'message' => 'Message d\'erreur',
        ]);
    }

    return new JsonResponse([
        'valid'   => true,
        'message' => '✓ Valeur valide',
    ]);
}
```

### 2. Côté Client (Optionnel)

Si vous voulez une validation locale avant l'appel AJAX, ajoutez un cas dans `localValidation()` du fichier `form-validation.js` :

```javascript
function localValidation(field, value, type) {
    switch (type) {
        case 'email':
            // ...
        case 'my_custom_type':
            if (!isValidMyCustomType(value)) {
                showValidationMessage(field, 'Message d\'erreur', 'error');
                return false;
            }
            break;
    }
    return true;
}
```

## Configuration Avancée

### Modifier le Délai de Debounce

Par défaut, la validation est déclenchée 500ms après la dernière frappe. Pour modifier ce délai :

```javascript
// Dans votre JavaScript custom
const VALIDATION_DELAY = 1000; // 1 seconde
```

### Désactiver la Validation sur Input

Par défaut, la validation se fait sur `input` et `blur`. Pour valider uniquement sur `blur` :

Retirez l'événement `input` dans le code JavaScript (lignes 188-200 de `form-validation.js`).

## API Endpoint

**Route** : `POST /api/validate`

**Corps de la requête** (JSON) :
```json
{
    "type": "email",
    "value": "test@example.com",
    "field": "client[email]",
    "exclude_id": 42  // Optionnel, pour édition
}
```

**Réponse succès** :
```json
{
    "valid": true,
    "message": "✓ Email valide"
}
```

**Réponse erreur** :
```json
{
    "valid": false,
    "message": "Format d'email invalide"
}
```

## Classes CSS Utilisées

- `.is-valid` : Champ valide (Bootstrap)
- `.is-invalid` : Champ invalide (Bootstrap)
- `.is-validating` : Champ en cours de validation (indicateur de chargement)
- `.validation-feedback` : Message de validation (succès/erreur)
- `.form-validation-error` : Message d'erreur global du formulaire

## Bonnes Pratiques

1. **Toujours valider côté serveur** : La validation JavaScript peut être contournée
2. **Messages clairs** : Indiquez exactement ce qui ne va pas et comment corriger
3. **Feedback immédiat** : Validez dès la sortie du champ (`blur`) pour une meilleure UX
4. **Performance** : Utilisez le debouncing pour limiter les appels API
5. **Accessibilité** : Les messages d'erreur sont visibles et associés aux champs

## Dépannage

### La validation ne se déclenche pas

- Vérifiez que le script `form-validation.js` est bien inclus
- Vérifiez que les attributs `data-validation-url` et `data-validation-type` sont présents
- Ouvrez la console navigateur pour voir les éventuelles erreurs

### Erreur 401 (Unauthorized)

L'endpoint `/api/validate` requiert une authentification (`ROLE_USER`). Assurez-vous que l'utilisateur est connecté.

### Validation trop lente

Augmentez le délai de debounce ou désactivez la validation sur `input` pour ne valider que sur `blur`.

## Exemples Réels dans le Projet

- **Client** : `templates/client/_form.html.twig` (nom, SIRET, email)
- **Plus à venir...**
