# Form Wizard Component

## Vue d'ensemble

Le composant Form Wizard permet de créer des formulaires multi-étapes avec barre de progression, navigation, validation par étape, et sauvegarde de l'état.

**Cas d'usage typiques** :
- Processus de commande en plusieurs étapes
- Inscription utilisateur complexe
- Configuration de projet en étapes
- Création de devis avec sections
- Onboarding utilisateur

## Fonctionnalités

- ✅ Formulaires multi-étapes avec navigation
- ✅ Barre de progression visuelle
- ✅ Indicateurs d'étapes numérotés (optionnel)
- ✅ Validation HTML5 par étape
- ✅ Validation personnalisée via événements
- ✅ Sauvegarde automatique de l'état dans localStorage
- ✅ Restauration de l'état au rechargement
- ✅ Navigation clavier (Entrée pour passer à l'étape suivante)
- ✅ Animations de transition entre étapes
- ✅ API JavaScript complète
- ✅ Événements personnalisés

## Installation

### 1. Inclure les Scripts et Styles

Le script et les styles sont compilés via Webpack Encore. Ajoutez-les dans votre template :

```twig
{% block stylesheets %}
    {{ parent() }}
    {# Les styles sont déjà dans app.css via @import "custom/components/wizard" #}
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('assets/form-wizard.js') }}"></script>
{% endblock %}
```

## Utilisation Basique

### 1. Structure HTML

```html
<div class="wizard-container" data-wizard="true">
    <!-- Progress bar (auto-generated if not present) -->
    <div class="wizard-progress mb-4">
        <div class="wizard-progress-bar"></div>
    </div>

    <!-- Steps container -->
    <div class="wizard-steps">
        <!-- Step 1 -->
        <div data-wizard-step="1" data-wizard-title="Informations de base">
            <h3>Étape 1 : Informations de base</h3>
            <div class="mb-3">
                <label class="form-label">Nom du projet *</label>
                <input type="text" name="project_name" class="form-control" required>
            </div>

            <div class="wizard-actions">
                <button type="button" class="btn btn-primary" data-wizard-action="next">
                    Suivant <i class="bx bx-right-arrow-alt ms-1"></i>
                </button>
            </div>
        </div>

        <!-- Step 2 -->
        <div data-wizard-step="2" data-wizard-title="Détails">
            <h3>Étape 2 : Détails du projet</h3>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="4"></textarea>
            </div>

            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-wizard-action="prev">
                    <i class="bx bx-left-arrow-alt me-1"></i> Précédent
                </button>
                <button type="button" class="btn btn-primary" data-wizard-action="next">
                    Suivant <i class="bx bx-right-arrow-alt ms-1"></i>
                </button>
            </div>
        </div>

        <!-- Step 3 -->
        <div data-wizard-step="3" data-wizard-title="Confirmation">
            <h3>Étape 3 : Confirmation</h3>
            <div class="alert alert-info">
                Veuillez vérifier les informations avant de soumettre.
            </div>

            <div class="wizard-actions">
                <button type="button" class="btn btn-secondary" data-wizard-action="prev">
                    <i class="bx bx-left-arrow-alt me-1"></i> Précédent
                </button>
                <button type="submit" class="btn btn-success" data-wizard-action="submit">
                    <i class="bx bx-check me-1"></i> Soumettre
                </button>
            </div>
        </div>
    </div>
</div>
```

### 2. Avec Indicateurs d'Étapes

Ajoutez `data-wizard-show-steps="true"` pour afficher les indicateurs numérotés :

```html
<div class="wizard-container" data-wizard="true" data-wizard-show-steps="true">
    <div class="wizard-steps">
        <div data-wizard-step="1" data-wizard-title="Informations">
            <!-- contenu -->
        </div>
        <div data-wizard-step="2" data-wizard-title="Options">
            <!-- contenu -->
        </div>
        <div data-wizard-step="3" data-wizard-title="Validation">
            <!-- contenu -->
        </div>
    </div>
</div>
```

## Attributs de Configuration

### Sur le Conteneur `data-wizard`

#### `data-wizard="true"` (Requis)

Active le wizard sur ce conteneur.

#### `data-wizard-save-state="true"` (Optionnel)

Sauvegarde automatiquement l'état du wizard dans localStorage. L'état est restauré au rechargement de la page.

```html
<div data-wizard="true" data-wizard-save-state="true">
```

#### `data-wizard-state-key="unique-key"` (Optionnel)

Clé unique pour le stockage dans localStorage. Par défaut : `"wizard-state"`.

```html
<div data-wizard="true" data-wizard-save-state="true" data-wizard-state-key="project-wizard">
```

#### `data-wizard-validate="false"` (Optionnel)

Désactive la validation automatique lors du passage à l'étape suivante. Par défaut : `true`.

```html
<div data-wizard="true" data-wizard-validate="false">
```

#### `data-wizard-show-steps="true"` (Optionnel)

Affiche les indicateurs d'étapes numérotés avec titres. Par défaut : `false`.

```html
<div data-wizard="true" data-wizard-show-steps="true">
```

#### `data-wizard-enter-next="true"` (Optionnel)

Active la navigation avec la touche Entrée pour passer à l'étape suivante.

```html
<div data-wizard="true" data-wizard-enter-next="true">
```

### Sur les Étapes `data-wizard-step`

#### `data-wizard-step="n"` (Requis)

Numéro de l'étape (commençant à 1).

```html
<div data-wizard-step="1">
<div data-wizard-step="2">
```

#### `data-wizard-title="Titre"` (Optionnel)

Titre de l'étape affiché dans les indicateurs.

```html
<div data-wizard-step="1" data-wizard-title="Informations de base">
```

### Sur les Boutons de Navigation

#### `data-wizard-action="next|prev|submit"` (Requis)

Action à effectuer au clic.

```html
<button data-wizard-action="next">Suivant</button>
<button data-wizard-action="prev">Précédent</button>
<button data-wizard-action="submit">Soumettre</button>
```

## Exemples Complets

### Exemple 1 : Création de Projet

```twig
{# templates/project/_wizard_form.html.twig #}
<form method="post" action="{{ path('project_new') }}">
    <div class="wizard-container card" data-wizard="true" data-wizard-save-state="true" data-wizard-show-steps="true" data-wizard-state-key="new-project-wizard">
        <div class="card-body">
            <div class="wizard-steps">
                <!-- Étape 1 : Informations générales -->
                <div data-wizard-step="1" data-wizard-title="Informations">
                    <h4 class="mb-4">Informations générales</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nom du projet *</label>
                                <input type="text" name="project[name]" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Client *</label>
                                <select name="project[client]" class="form-select" required>
                                    <option value="">Sélectionner...</option>
                                    {% for client in clients %}
                                        <option value="{{ client.id }}">{{ client.name }}</option>
                                    {% endfor %}
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-primary" data-wizard-action="next">
                            Suivant <i class="bx bx-right-arrow-alt ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- Étape 2 : Détails techniques -->
                <div data-wizard-step="2" data-wizard-title="Technique">
                    <h4 class="mb-4">Détails techniques</h4>
                    <div class="mb-3">
                        <label class="form-label">Technologies</label>
                        <select name="project[technologies][]" class="form-select" multiple>
                            {% for tech in technologies %}
                                <option value="{{ tech.id }}">{{ tech.name }}</option>
                            {% endfor %}
                        </select>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-secondary" data-wizard-action="prev">
                            <i class="bx bx-left-arrow-alt me-1"></i> Précédent
                        </button>
                        <button type="button" class="btn btn-primary" data-wizard-action="next">
                            Suivant <i class="bx bx-right-arrow-alt ms-1"></i>
                        </button>
                    </div>
                </div>

                <!-- Étape 3 : Budget -->
                <div data-wizard-step="3" data-wizard-title="Budget">
                    <h4 class="mb-4">Configuration budgétaire</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Budget estimé (€)</label>
                                <input type="number" name="project[estimated_budget]" class="form-control" step="0.01">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Type de facturation</label>
                                <select name="project[billing_type]" class="form-select">
                                    <option value="forfait">Forfait</option>
                                    <option value="regie">Régie</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="wizard-actions">
                        <button type="button" class="btn btn-secondary" data-wizard-action="prev">
                            <i class="bx bx-left-arrow-alt me-1"></i> Précédent
                        </button>
                        <button type="submit" class="btn btn-success" data-wizard-action="submit">
                            <i class="bx bx-save me-1"></i> Créer le projet
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{ form_rest(form) }}
</form>

{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('assets/form-wizard.js') }}"></script>
{% endblock %}
```

### Exemple 2 : Validation Personnalisée

```javascript
// Écouter l'événement de validation personnalisée
document.querySelector('.wizard-container').addEventListener('wizard:validate-step', function(e) {
    const step = e.detail.step;

    if (step === 2) {
        // Validation personnalisée pour l'étape 2
        const techSelect = document.querySelector('[name="project[technologies][]"]');
        if (techSelect.selectedOptions.length === 0) {
            alert('Veuillez sélectionner au moins une technologie');
            e.preventDefault(); // Empêche le passage à l'étape suivante
        }
    }
});
```

### Exemple 3 : Écouter les Événements

```javascript
const wizardContainer = document.querySelector('.wizard-container');

// Changement d'étape
wizardContainer.addEventListener('wizard:step-changed', function(e) {
    console.log('Étape changée:', e.detail.step, '/', e.detail.totalSteps);
    console.log('Première étape:', e.detail.isFirst);
    console.log('Dernière étape:', e.detail.isLast);
});

// Initialisation
wizardContainer.addEventListener('wizard:init', function(e) {
    console.log('Wizard initialisé avec', e.detail.totalSteps, 'étapes');
});

// Soumission
wizardContainer.addEventListener('wizard:submit', function(e) {
    console.log('Formulaire soumis après', e.detail.totalSteps, 'étapes');
});

// Validation échouée
wizardContainer.addEventListener('wizard:validation-failed', function(e) {
    console.log('Validation échouée à l\'étape', e.detail.step);
});

// Soumission échouée
wizardContainer.addEventListener('wizard:submit-failed', function(e) {
    console.log('Soumission échouée, étape invalide:', e.detail.failedStep);
});
```

## API JavaScript

### Initialisation Manuelle

```javascript
import { initWizards } from './form-wizard.js';

// Initialiser tous les wizards de la page
initWizards();
```

### Création Programmatique

```javascript
import { createWizard } from './form-wizard.js';

const container = document.querySelector('.wizard-container');

const wizard = createWizard(container, {
    saveState: true,
    stateKey: 'my-wizard',
    validate: true,
    showSteps: true,
    enterNext: true
});
```

### Méthodes

#### `next()` : Aller à l'étape suivante

```javascript
wizard.next();
```

#### `prev()` : Retourner à l'étape précédente

```javascript
wizard.prev();
```

#### `goToStep(n)` : Aller à une étape spécifique

```javascript
wizard.goToStep(3); // Aller à l'étape 3
```

#### `reset()` : Réinitialiser le wizard

```javascript
wizard.reset(); // Retour à l'étape 1, réinitialise le formulaire et l'état
```

#### `getCurrentStep()` : Obtenir l'étape courante

```javascript
const currentStep = wizard.getCurrentStep();
console.log('Étape actuelle:', currentStep);
```

#### `getTotalSteps()` : Obtenir le nombre total d'étapes

```javascript
const totalSteps = wizard.getTotalSteps();
console.log('Nombre d\'étapes:', totalSteps);
```

## Événements Personnalisés

| Événement | Description | Détail |
|-----------|-------------|--------|
| `wizard:init` | Déclenché après l'initialisation | `{ step, totalSteps }` |
| `wizard:step-changed` | Déclenché au changement d'étape | `{ step, totalSteps, isFirst, isLast }` |
| `wizard:validate-step` | Déclenché avant validation d'étape | `{ step, isValid }` |
| `wizard:validation-failed` | Déclenché si validation échoue | `{ step }` |
| `wizard:submit` | Déclenché à la soumission | `{ totalSteps }` |
| `wizard:submit-failed` | Déclenché si soumission échoue | `{ failedStep }` |
| `wizard:reset` | Déclenché après réinitialisation | `{}` |

## Classes CSS

### Structure

- `.wizard-container` : Conteneur principal
- `.wizard-progress` : Conteneur de la barre de progression
- `.wizard-progress-bar` : Barre de progression (0-100%)
- `.wizard-step-indicators` : Conteneur des indicateurs d'étapes
- `.wizard-step-indicator` : Un indicateur d'étape
- `.step-number` : Numéro de l'étape
- `.step-title` : Titre de l'étape
- `.wizard-steps` : Conteneur des étapes
- `.wizard-actions` : Conteneur des boutons de navigation

### États

- `.active` : Étape/indicateur actif
- `.completed` : Indicateur d'étape complétée

## Personnalisation CSS

```scss
// Changer la couleur de la barre de progression
.wizard-progress-bar {
    background: linear-gradient(to right, #007bff, #28a745);
}

// Personnaliser les indicateurs d'étapes
.step-number.active {
    background-color: #007bff;
}

.step-number.completed {
    background-color: #28a745;
}

// Animation personnalisée
[data-wizard-step] {
    animation: customFadeIn 0.5s ease;
}

@keyframes customFadeIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}
```

## Bonnes Pratiques

1. **Validation progressive** : Validez chaque étape avant de permettre la navigation
2. **Sauvegarde d'état** : Utilisez `data-wizard-save-state="true"` pour les formulaires longs
3. **Indicateurs clairs** : Utilisez `data-wizard-show-steps="true"` pour montrer la progression
4. **Feedback visuel** : Gardez les styles par défaut ou personnalisez-les cohéremment
5. **Accessibilité** : Assurez-vous que les boutons de navigation sont bien identifiés
6. **Messages d'erreur** : Affichez clairement les erreurs de validation
7. **Confirmation** : Ajoutez une étape de confirmation avant soumission finale

## Dépannage

### Le wizard ne s'initialise pas

- Vérifiez que `data-wizard="true"` est présent sur le conteneur
- Vérifiez que le script `form-wizard.js` est bien chargé
- Ouvrez la console pour voir les erreurs

### Les étapes ne se cachent pas

- Vérifiez que les attributs `data-wizard-step` sont bien numérotés
- Vérifiez qu'il n'y a pas de CSS personnalisé qui force le `display`

### La validation ne fonctionne pas

- Vérifiez que les champs ont l'attribut `required` ou d'autres contraintes HTML5
- Vérifiez que `data-wizard-validate="false"` n'est pas défini
- Écoutez l'événement `wizard:validation-failed` pour déboguer

### L'état n'est pas sauvegardé

- Vérifiez que `data-wizard-save-state="true"` est présent
- Vérifiez que localStorage est disponible dans le navigateur
- Utilisez une clé unique avec `data-wizard-state-key` si vous avez plusieurs wizards

## Exemples Réels dans le Projet

- **À implémenter** : Création de projet (formulaire multi-étapes)
- **À implémenter** : Création de devis (plusieurs sections)
