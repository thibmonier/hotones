# Design System HotOnes

Ce document définit le design system de l'application HotOnes, incluant les composants réutilisables, les patterns CRUD standardisés, et les bonnes pratiques d'interface.

## Table des matières

- [Composants de base](#composants-de-base)
- [Templates CRUD](#templates-crud)
- [Iconographie](#iconographie)
- [Couleurs et thèmes](#couleurs-et-thèmes)
- [Typographie](#typographie)
- [Patterns communs](#patterns-communs)
- [Accessibilité](#accessibilité)

---

## Composants de base

### Page Header

**Fichier** : `templates/components/page_header.html.twig`

Affiche un en-tête de page standardisé avec titre, breadcrumb et boutons d'action.

```twig
{% include 'components/page_header.html.twig' with {
    title: 'Liste des clients',
    breadcrumb: [
        {label: 'Clients', path: null}
    ],
    actions: [
        {label: 'Nouveau client', path: 'client_new', icon: 'bx-plus', class: 'btn-primary'}
    ]
} %}
```

**Paramètres** :
- `title` (string, requis) : Titre de la page
- `breadcrumb` (array, optionnel) : Fil d'ariane
- `actions` (array, optionnel) : Boutons d'action

### Breadcrumb

**Fichier** : `templates/components/breadcrumb.html.twig`

Fil d'ariane pour la navigation.

```twig
{% include 'components/breadcrumb.html.twig' with {
    items: [
        {label: 'Clients', path: 'client_index'},
        {label: 'Détails', path: null}
    ]
} %}
```

### DataTable

**Fichier** : `templates/components/data_table.html.twig`

Tableau de données avec tri, pagination, et actions en masse.

```twig
{% include 'components/data_table.html.twig' with {
    columns: [
        {key: 'name', label: 'Nom', sortable: true},
        {key: 'email', label: 'Email', sortable: false},
        {key: 'actions', label: 'Actions', sortable: false}
    ],
    data: clients,
    actions: {
        show: {route: 'client_show', icon: 'bx-show', label: 'Voir'},
        edit: {route: 'client_edit', icon: 'bx-edit', label: 'Modifier'},
        delete: {route: 'client_delete', icon: 'bx-trash', label: 'Supprimer', confirm: true}
    },
    massActions: true,
    currentPage: page,
    totalPages: totalPages,
    itemsPerPage: 25,
    totalItems: total
} %}
```

### Pagination

**Fichier** : `templates/components/pagination.html.twig`

Pagination intelligente avec sélecteur d'éléments par page.

```twig
{% include 'components/pagination.html.twig' with {
    currentPage: page,
    totalPages: totalPages,
    itemsPerPage: 25,
    totalItems: total
} %}
```

### Filter Panel

**Fichier** : `templates/components/filter_panel.html.twig`

Panneau de filtres collapsible avec compteur de filtres actifs.

```twig
{% include 'components/filter_panel.html.twig' with {
    filters: [
        {type: 'text', name: 'search', label: 'Recherche', value: search},
        {type: 'select', name: 'status', label: 'Statut', options: statuses, value: status},
        {type: 'date', name: 'date_from', label: 'Date début', value: dateFrom}
    ],
    activeCount: activeFilterCount
} %}
```

**Types de filtres supportés** :
- `text` : Champ texte
- `select` : Liste déroulante
- `date` : Sélecteur de date
- `checkbox` : Case à cocher

---

## Templates CRUD

### Liste (Index)

**Fichier** : `templates/crud/list.html.twig`

Template standard pour les pages de liste.

```twig
{% extends 'crud/list.html.twig' %}

{% set crud_config = {
    entity_name: 'client',
    entity_name_plural: 'clients',
    entity_label: 'Client',
    entity_label_plural: 'Clients',
    breadcrumb: [{label: 'Clients', path: null}],
    can_create: is_granted('ROLE_CHEF_PROJET'),
    create_route: 'client_new',
    filters: [
        {type: 'text', name: 'search', label: 'Recherche', value: app.request.query.get('search')},
        {type: 'select', name: 'status', label: 'Statut', options: statuses, value: app.request.query.get('status')}
    ],
    columns: [
        {key: 'name', label: 'Nom', sortable: true},
        {key: 'email', label: 'Email', sortable: false},
        {key: 'actions', label: 'Actions', sortable: false}
    ],
    actions: {
        show: {route: 'client_show', icon: 'bx-show', label: 'Voir'},
        edit: {route: 'client_edit', icon: 'bx-edit', label: 'Modifier', permission: 'ROLE_CHEF_PROJET'},
        delete: {route: 'client_delete', icon: 'bx-trash', label: 'Supprimer', confirm: true, permission: 'ROLE_ADMIN'}
    },
    mass_actions: true,
    show_stats: true
} %}

{% set items = clients %}
{% set pagination = {
    current_page: page,
    total_pages: totalPages,
    per_page: 25,
    total: total
} %}
```

### Formulaire (New/Edit)

**Fichier** : `templates/crud/form.html.twig`

Template standard pour les formulaires de création/édition.

```twig
{% extends 'crud/form.html.twig' %}

{% set crud_config = {
    entity_name: 'client',
    entity_label: 'Client',
    is_edit: client.id is defined,
    breadcrumb: [
        {label: 'Clients', path: 'client_index'},
        {label: client.id is defined ? 'Modifier' : 'Nouveau', path: null}
    ],
    list_route: 'client_index',
    show_route: 'client_show',
    delete_route: 'client_delete',
    form: form,
    validate_on_submit: true,
    show_help: true
} %}

{% block form_content %}
    <div class="mb-3">
        {{ form_label(form.name) }}
        {{ form_widget(form.name, {
            'attr': {
                'data-validation-url': path('api_validate'),
                'data-validation-type': 'client_name_unique',
                'class': 'form-control'
            }
        }) }}
        {{ form_errors(form.name) }}
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
        {{ form_errors(form.email) }}
    </div>

    {{ form_rest(form) }}
{% endblock %}

{% block help_content %}
    <p class="text-muted small">
        <strong>Nom du client</strong> : Nom commercial de l'entreprise
    </p>
    <p class="text-muted small">
        <strong>Email</strong> : Adresse email principale de contact
    </p>
{% endblock %}
```

### Détails (Show)

**Fichier** : `templates/crud/show.html.twig`

Template standard pour les pages de détails.

```twig
{% extends 'crud/show.html.twig' %}

{% set crud_config = {
    entity_name: 'client',
    entity_label: 'Client',
    entity: client,
    breadcrumb: [
        {label: 'Clients', path: 'client_index'},
        {label: client.name, path: null}
    ],
    list_route: 'client_index',
    edit_route: 'client_edit',
    delete_route: 'client_delete',
    can_edit: is_granted('ROLE_CHEF_PROJET'),
    can_delete: is_granted('ROLE_ADMIN'),
    show_metadata: true,
    custom_actions: [
        {label: 'Voir les projets', path: 'project_index', icon: 'bx-folder', class: 'btn-outline-primary', params: {client: client.id}}
    ]
} %}

{% block entity_details %}
    <dl class="row">
        <dt class="col-sm-3">Nom</dt>
        <dd class="col-sm-9">{{ client.name }}</dd>

        <dt class="col-sm-3">SIRET</dt>
        <dd class="col-sm-9">{{ client.siret|default('—') }}</dd>

        <dt class="col-sm-3">Email</dt>
        <dd class="col-sm-9">
            {% if client.email %}
                <a href="mailto:{{ client.email }}">{{ client.email }}</a>
            {% else %}
                —
            {% endif %}
        </dd>

        <dt class="col-sm-3">Adresse</dt>
        <dd class="col-sm-9">{{ client.address|nl2br|default('—') }}</dd>
    </dl>
{% endblock %}

{% block additional_sections %}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bx bx-folder-open me-2"></i>
                Projets associés ({{ client.projects|length }})
            </h5>

            {% if client.projects|length > 0 %}
                <div class="list-group">
                    {% for project in client.projects|slice(0, 5) %}
                        <a href="{{ path('project_show', {id: project.id}) }}" class="list-group-item list-group-item-action">
                            {{ project.name }}
                        </a>
                    {% endfor %}
                </div>

                {% if client.projects|length > 5 %}
                    <a href="{{ path('project_index', {client: client.id}) }}" class="btn btn-sm btn-link mt-2">
                        Voir tous les projets ({{ client.projects|length }})
                    </a>
                {% endif %}
            {% else %}
                <p class="text-muted">Aucun projet associé</p>
            {% endif %}
        </div>
    </div>
{% endblock %}

{% block sidebar %}
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">
                <i class="bx bx-bar-chart me-2"></i>
                Statistiques
            </h5>

            <dl class="row mb-0">
                <dt class="col-sm-6 text-muted small">Projets actifs</dt>
                <dd class="col-sm-6 small text-end">
                    <strong>{{ client.activeProjects|length }}</strong>
                </dd>

                <dt class="col-sm-6 text-muted small">CA total</dt>
                <dd class="col-sm-6 small text-end">
                    <strong>{{ client.totalRevenue|number_format(0, ',', ' ') }} €</strong>
                </dd>
            </dl>
        </div>
    </div>
{% endblock %}
```

---

## Iconographie

### Boxicons

L'application utilise la bibliothèque **Boxicons** pour tous les icônes.

**CDN** : `https://cdn.jsdelivr.net/npm/boxicons@2.0.9/css/boxicons.min.css`

### Icônes standards par contexte

| Contexte | Icône | Classe |
|----------|-------|--------|
| **Navigation** | | |
| Accueil | 🏠 | `bx-home` |
| Retour | ← | `bx-arrow-back` |
| Recherche | 🔍 | `bx-search` |
| Menu | ☰ | `bx-menu` |
| **Actions** | | |
| Créer/Ajouter | ➕ | `bx-plus` |
| Modifier/Éditer | ✏️ | `bx-edit` |
| Supprimer | 🗑️ | `bx-trash` |
| Enregistrer | 💾 | `bx-save` |
| Annuler | ✖️ | `bx-x` |
| Voir/Afficher | 👁️ | `bx-show` |
| Télécharger | ⬇️ | `bx-download` |
| Importer | ⬆️ | `bx-upload` |
| Exporter | 📤 | `bx-export` |
| Paramètres | ⚙️ | `bx-cog` |
| **Entités** | | |
| Utilisateur | 👤 | `bx-user` |
| Utilisateurs | 👥 | `bx-group` |
| Client | 🏢 | `bx-buildings` |
| Projet | 📁 | `bx-folder-open` |
| Tâche | ✓ | `bx-task` |
| Liste | 📋 | `bx-list-ul` |
| Calendrier | 📅 | `bx-calendar` |
| Temps | ⏱️ | `bx-time` |
| Graphique | 📊 | `bx-bar-chart` |
| Statistiques | 📈 | `bx-trending-up` |
| **États** | | |
| Succès | ✓ | `bx-check` |
| Erreur | ⚠️ | `bx-error` |
| Info | ℹ️ | `bx-info-circle` |
| Alerte | ⚡ | `bx-error-circle` |
| **Notifications** | | |
| Cloche | 🔔 | `bx-bell` |
| Message | 💬 | `bx-message` |
| Email | ✉️ | `bx-envelope` |

---

## Couleurs et thèmes

### Palette principale

L'application utilise Bootstrap 5 avec le thème **Skote**.

| Couleur | Hex | Usage |
|---------|-----|-------|
| **Primary** | `#556ee6` | Actions principales, liens |
| **Success** | `#34c38f` | Succès, validation |
| **Danger** | `#f46a6a` | Erreurs, suppressions |
| **Warning** | `#f1b44c` | Alertes, avertissements |
| **Info** | `#50a5f1` | Informations |
| **Dark** | `#343a40` | Texte, header |
| **Light** | `#f8f9fa` | Backgrounds, sidebar |

### Classes utilitaires

```html
<!-- Backgrounds -->
<div class="bg-primary text-white">...</div>
<div class="bg-success text-white">...</div>
<div class="bg-danger text-white">...</div>

<!-- Texte -->
<p class="text-primary">...</p>
<p class="text-muted">...</p>

<!-- Badges -->
<span class="badge bg-primary">Actif</span>
<span class="badge bg-success">Validé</span>
<span class="badge bg-danger">Urgent</span>
```

---

## Typographie

### Hiérarchie des titres

```html
<h1 class="display-4">Titre principal</h1>
<h2 class="h2">Titre section</h2>
<h3 class="h3">Titre sous-section</h3>
<h4 class="h4">Titre carte</h4>
<h5 class="h5">Titre petit</h5>
```

### Classes utilitaires

```html
<!-- Tailles -->
<p class="font-size-12">Petit texte</p>
<p class="font-size-14">Texte normal</p>
<p class="font-size-18">Grand texte</p>
<p class="font-size-24">Très grand</p>

<!-- Poids -->
<p class="fw-light">Léger</p>
<p class="fw-normal">Normal</p>
<p class="fw-bold">Gras</p>

<!-- Couleurs -->
<p class="text-muted">Texte discret</p>
<p class="text-primary">Texte primaire</p>
```

---

## Patterns communs

### Cards

```html
<div class="card">
    <div class="card-body">
        <h5 class="card-title">
            <i class="bx bx-info-circle me-2"></i>
            Titre de la carte
        </h5>
        <p class="card-text">Contenu de la carte</p>
    </div>
</div>
```

### Listes de définitions

```html
<dl class="row">
    <dt class="col-sm-3">Label</dt>
    <dd class="col-sm-9">Valeur</dd>

    <dt class="col-sm-3">Autre label</dt>
    <dd class="col-sm-9">Autre valeur</dd>
</dl>
```

### Boutons

```html
<!-- Primaire -->
<button class="btn btn-primary">
    <i class="bx bx-save me-1"></i>
    Enregistrer
</button>

<!-- Secondaire -->
<button class="btn btn-light">
    <i class="bx bx-x me-1"></i>
    Annuler
</button>

<!-- Danger -->
<button class="btn btn-danger">
    <i class="bx bx-trash me-1"></i>
    Supprimer
</button>

<!-- Outline -->
<button class="btn btn-outline-secondary">
    <i class="bx bx-download me-1"></i>
    Exporter
</button>

<!-- Tailles -->
<button class="btn btn-primary btn-sm">Petit</button>
<button class="btn btn-primary">Normal</button>
<button class="btn btn-primary btn-lg">Grand</button>
```

### Messages flash

Les messages flash sont affichés automatiquement dans `base.html.twig` :

```php
// Dans le contrôleur
$this->addFlash('success', 'Client créé avec succès');
$this->addFlash('error', 'Une erreur est survenue');
$this->addFlash('warning', 'Attention : données incomplètes');
$this->addFlash('info', 'Information importante');
```

### États vides

```html
<div class="text-center text-muted py-5">
    <i class="bx bx-info-circle font-size-24 mb-2"></i>
    <div>Aucune donnée à afficher</div>
</div>
```

---

## Accessibilité

### Principes

1. **Contraste** : Ratio minimum 4.5:1 pour le texte
2. **Navigation au clavier** : Tous les éléments interactifs doivent être accessibles
3. **Labels** : Tous les champs de formulaire doivent avoir un label
4. **ARIA** : Utiliser les attributs ARIA pour les composants complexes

### Bonnes pratiques

```html
<!-- Boutons avec aria-label -->
<button type="button" aria-label="Supprimer" class="btn btn-danger">
    <i class="bx bx-trash"></i>
</button>

<!-- Images avec alt -->
<img src="..." alt="Description de l'image">

<!-- Liens explicites -->
<a href="...">Voir les détails du client</a>
<!-- Éviter: <a href="...">Cliquez ici</a> -->

<!-- Focus visible -->
<button class="btn btn-primary">Je suis focusable</button>
```

### Navigation au clavier

- `Tab` : Navigation entre les éléments
- `Shift + Tab` : Navigation inverse
- `Enter` : Activer un lien ou bouton
- `Space` : Activer une checkbox
- `Esc` : Fermer un modal
- `Ctrl+K` : Ouvrir la recherche globale

---

## Conventions de nommage

### Routes

```
entity_index    # Liste
entity_show     # Détails
entity_new      # Création
entity_edit     # Édition
entity_delete   # Suppression
```

### Templates

```
entity/index.html.twig
entity/show.html.twig
entity/new.html.twig
entity/edit.html.twig
```

### Classes CSS custom

Préfixer avec `app-` pour éviter les conflits :

```css
.app-header-action { ... }
.app-sidebar-item { ... }
```

---

## Responsive Design

### Breakpoints Bootstrap 5

- `xs` : < 576px
- `sm` : ≥ 576px
- `md` : ≥ 768px
- `lg` : ≥ 992px
- `xl` : ≥ 1200px
- `xxl` : ≥ 1400px

### Classes utilitaires responsive

```html
<!-- Affichage conditionnel -->
<div class="d-none d-md-block">Visible sur desktop uniquement</div>
<div class="d-block d-md-none">Visible sur mobile uniquement</div>

<!-- Colonnes responsive -->
<div class="col-12 col-md-6 col-lg-4">Responsive column</div>
```

---

## Performance

### Images

- Toujours définir `width` et `height`
- Utiliser lazy loading : `loading="lazy"`
- Formats modernes : WebP avec fallback

### JavaScript

- Modules chargés de manière asynchrone
- Debounce pour validation AJAX (500ms)
- Polling notifications intelligent (pause si page cachée)

### CSS

- Utiliser les classes utilitaires Bootstrap plutôt que custom CSS
- Éviter les `!important`
- Minification en production

---

## Ressources

- **Bootstrap 5 Docs** : https://getbootstrap.com/docs/5.3/
- **Boxicons** : https://boxicons.com/
- **Skote Theme** : Template de base utilisé
- **Symfony UX** : https://ux.symfony.com/

---

---

## Phase 5 - Standards 2025 (Lot 15.5)

Cette section documente les standardisations établies lors de la Phase 5 (novembre 2025).

### Nouveaux components

#### card_section.html.twig

Component pour sections de formulaires standardisées.

```twig
{% embed 'components/card_section.html.twig' with {
    title: 'Informations générales',
    icon: 'bx-user'
} %}
    {% block content %}
        <div class="mb-3">
            <label class="form-label">Nom <span class="text-danger">*</span></label>
            <input type="text" class="form-control" required>
        </div>
    {% endblock %}
{% endembed %}
```

#### form_buttons.html.twig

Boutons de formulaire standardisés avec layouts horizontal/vertical.

```twig
{# Layout horizontal (défaut) #}
{% include 'components/form_buttons.html.twig' with {
    cancel_route: 'entity_index',
    submit_label: 'Créer',
    layout: 'horizontal'
} %}

{# Layout vertical (sidebar) #}
{% include 'components/form_buttons.html.twig' with {
    cancel_route: 'entity_index',
    submit_label: 'Enregistrer',
    layout: 'vertical',
    show_delete: true,
    delete_route: 'entity_delete',
    delete_params: {id: entity.id}
} %}
```

### Standards de formulaires (2025)

#### Titres de cartes OBLIGATOIRES

**Règle stricte** : Toujours `<h5>` avec `mb-0` et icône optionnelle dans un `<div class="card-header">`.

```html
<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">
            <i class="bx bx-user me-2"></i>
            Informations générales
        </h5>
    </div>
    <div class="card-body">
        <!-- Contenu -->
    </div>
</div>
```

❌ **À éviter** :
```html
<!-- PAS de h4 -->
<h4 class="card-title">Titre</h4>

<!-- PAS sans mb-0 -->
<h5 class="card-title">Titre</h5>

<!-- PAS dans card-body directement sans header -->
<div class="card-body">
    <h5>Titre</h5>
</div>
```

#### Icônes standards par section

| Section | Icône Boxicons |
|---------|----------------|
| Informations générales | `bx-user` ou `bx-info-circle` |
| Informations financières | `bx-euro` |
| Profils métier / Compétences professionnelles | `bx-briefcase` |
| Compétences techniques | `bx-star` |
| Technologies | `bx-code-alt` |
| Catégories | `bx-category` |
| Aide / Information | `bx-help-circle` |
| Métadonnées / Dates | `bx-time` ou `bx-calendar` |
| Statistiques | `bx-bar-chart` ou `bx-info-circle` |

#### Boutons d'action standardisés

**Classes** :
- `btn-primary` : Action principale (Enregistrer, Créer)
- `btn-outline-secondary` : Annuler
- `btn-outline-danger` ou `btn-danger` : Supprimer

**Icônes** :
- `bx-save` : Enregistrer
- `bx-x` : Annuler
- `bx-trash` : Supprimer
- `bx-arrow-back` : Retour
- `bx-plus` : Nouveau/Créer

**Labels simplifiés** :
- Création : "Créer" (non "Créer le XXX")
- Modification : "Enregistrer" (non "Enregistrer les modifications")
- Annulation : "Annuler"

**Espacement** : Toujours utiliser `gap-2` au lieu de `me-2` entre boutons.

```html
<!-- Horizontal -->
<div class="d-flex justify-content-end gap-2">
    <a href="..." class="btn btn-outline-secondary">
        <i class="bx bx-x me-1"></i> Annuler
    </a>
    <button type="submit" class="btn btn-primary">
        <i class="bx bx-save me-1"></i> Enregistrer
    </button>
</div>

<!-- Vertical (sidebar) -->
<div class="d-grid gap-2">
    <button type="submit" class="btn btn-primary">
        <i class="bx bx-save me-1"></i> Créer
    </button>
    <a href="..." class="btn btn-outline-secondary">
        <i class="bx bx-x me-1"></i> Annuler
    </a>
</div>
```

### Standards de listes (2025)

#### Pagination KnpPaginator

**Propriétés à utiliser** :
- `currentPageNumber` : Numéro de page actuelle
- `pageCount` : Nombre total de pages
- `totalItemCount` : Nombre total d'items

❌ **Ne PAS utiliser** : `currentPageOffsetStart`, `currentPageOffsetEnd` (n'existent pas)

**Format standard** :
```twig
{% if items.pageCount > 1 %}
<div class="d-flex justify-content-between align-items-center mt-3">
    <div class="text-muted">
        Page {{ items.currentPageNumber }} sur {{ items.pageCount }}
        ({{ items.totalItemCount }} items au total)
    </div>
    <div>
        {{ knp_pagination_render(items) }}
    </div>
</div>
{% endif %}
```

#### Filtres standard

Tous les index doivent inclure :
1. Recherche textuelle (nom, description)
2. Filtre statut/catégorie
3. Sélecteur "Par page" (10, 25, 50, 100)
4. Boutons Filtrer + Réinitialiser

```twig
<div class="col-md-2">
    <label class="form-label">Par page</label>
    <select class="form-select form-select-sm" name="per_page">
        <option value="10" {% if app.request.query.get('per_page') == '10' %}selected{% endif %}>10</option>
        <option value="25" {% if app.request.query.get('per_page', '25') == '25' %}selected{% endif %}>25</option>
        <option value="50" {% if app.request.query.get('per_page') == '50' %}selected{% endif %}>50</option>
        <option value="100" {% if app.request.query.get('per_page') == '100' %}selected{% endif %}>100</option>
    </select>
</div>
```

### Standards dashboards & Chart.js (2025)

#### Conteneurs de graphiques OBLIGATOIRES

**Règle stricte** : Toujours wrapper les canvas dans un div avec `height` fixe et `position: relative`.

```twig
<div style="height: 200px; position: relative;">
    <canvas id="myChart"></canvas>
</div>
```

**Hauteurs recommandées** :
- Graphiques simples : 200px
- Graphiques avec légende détaillée : 300px
- Graphiques complexes/multi-séries : 400px
- Maximum : 400px

#### Configuration Chart.js standard

```javascript
new Chart(ctx, {
    type: 'bar',
    data: { ... },
    options: {
        responsive: true,
        maintainAspectRatio: false,  // ← OBLIGATOIRE
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});
```

❌ **Erreur courante** : Oublier `maintainAspectRatio: false` + conteneur fixe = graphique qui grandit infiniment.

#### Palette de couleurs Chart.js

```javascript
const chartColors = {
    primary: 'rgba(52, 143, 226, 0.8)',
    success: 'rgba(40, 167, 69, 0.8)',
    danger: 'rgba(220, 53, 69, 0.8)',
    warning: 'rgba(255, 193, 7, 0.8)',
    info: 'rgba(23, 162, 184, 0.8)',
    purple: 'rgba(153, 102, 255, 0.8)',
    gray: 'rgba(201, 203, 207, 0.8)'
};

// Niveaux de compétences (exemple)
const levelColors = {
    1: 'rgba(201, 203, 207, 0.8)',   // Débutant - Gris
    2: 'rgba(255, 205, 86, 0.8)',    // Intermédiaire - Jaune
    3: 'rgba(54, 162, 235, 0.8)',    // Confirmé - Bleu
    4: 'rgba(75, 192, 192, 0.8)',    // Expert - Vert
    5: 'rgba(153, 102, 255, 0.8)'    // Maître - Violet
};
```

### Checklists standardisées

#### ✅ Checklist nouvelle page de liste

- [ ] Utiliser `page_header` component avec breadcrumb complète
- [ ] Panel de filtres avec recherche + statut + per_page
- [ ] Appliquer filtres dans QueryBuilder (controller)
- [ ] Utiliser KnpPaginator avec config par défaut 25/page
- [ ] Afficher pagination avec format "Page X sur Y (Z total)"
- [ ] Bouton Export CSV dans actions (permission ROLE_MANAGER minimum)
- [ ] Méthode `exportCsv()` avec filtres identiques à `index()`
- [ ] Bouton "Nouveau" dans actions (avec permission appropriée)

#### ✅ Checklist nouveau formulaire

- [ ] Utiliser `page_header` component avec breadcrumb complète
- [ ] Choisir layout : simple (col-lg-8) ou sidebar (col-lg-8 + col-lg-4)
- [ ] Tous les titres de section en `<h5 class="card-title mb-0">` dans `card-header`
- [ ] Icônes appropriées pour chaque section (voir tableau ci-dessus)
- [ ] Champs requis avec `<span class="text-danger">*</span>` dans le label
- [ ] Aides contextuelles avec `<div class="form-text">` si nécessaire
- [ ] Boutons avec classes standard (primary/outline-secondary)
- [ ] Labels de boutons simplifiés ("Créer", "Enregistrer", "Annuler")
- [ ] Espacement avec `gap-2` sur conteneurs de boutons
- [ ] Token CSRF pour toute action POST

#### ✅ Checklist nouveau dashboard

- [ ] Wrapper tous les canvas dans `<div style="height: Xpx; position: relative;">`
- [ ] Configurer `maintainAspectRatio: false` dans options Chart.js
- [ ] Respecter hauteurs max (200-400px)
- [ ] Utiliser palette de couleurs standardisée
- [ ] Légendes en position `bottom`
- [ ] Tester responsive sur mobile

### JavaScript modules

#### mass-actions.js

**Fichier** : `assets/js/mass-actions.js`

Module pour gestion des actions en masse sur tableaux.

**Fonctionnalités** :
- Sélection multiple avec "Tout sélectionner"
- État indeterminate si sélection partielle
- Barre d'actions avec compteur
- Suppression en masse avec confirmation
- Export en masse
- Tri de colonnes

**Auto-activation** : Si table avec ID `#dataTable` et checkbox `#selectAll`.

#### form-validation.js

**Fichier** : `assets/js/form-validation.js`

Module pour validation temps réel des formulaires.

**Fonctionnalités** :
- Validation locale (email, téléphone, URL, SIRET, date, etc.)
- Validation AJAX serveur
- Debouncing 500ms
- Messages d'erreur contextuels
- Indicateur de chargement

**Activation** :
```html
<input type="text"
       data-validation-url="{{ path('api_validate') }}"
       data-validation-type="email">
```

---

### Nouveaux composants Phase 6 - Lot 9 (Décembre 2025)

#### status_badge.html.twig

**Fichier** : `templates/components/status_badge.html.twig`

Badge de statut coloré avec icône automatique selon le type et le statut.

```twig
{% include 'components/status_badge.html.twig' with {
    status: 'actif',
    type: 'project',
    size: 'md'
} %}
```

**Paramètres** :
- `status` (string, requis) : Statut à afficher
- `type` (string, optionnel) : Type de badge - `project`, `order`, `task`, `payment`, `user`, `generic` (défaut)
- `size` (string, optionnel) : Taille - `sm`, `md` (défaut), `lg`
- `icon` (string, optionnel) : Icône Boxicons personnalisée (remplace l'icône par défaut)
- `custom_class` (string, optionnel) : Classes CSS additionnelles

**Types de statuts supportés** :
- **Project** : `actif`, `en_attente`, `termine`, `archive`, `en_pause`
- **Order** : `brouillon`, `envoye`, `en_attente`, `a_signer`, `signe`, `gagne`, `perdu`, `annule`
- **Task** : `a_faire`, `en_cours`, `en_test`, `termine`, `bloque`
- **Payment** : `en_attente`, `paye`, `en_retard`, `annule`
- **User** : `actif`, `inactif`, `suspendu`, `bloque`
- **Generic** : `succes`, `info`, `avertissement`, `erreur`, `neutre`, `primaire`

**Exemples** :
```twig
{# Badge projet avec icône et couleur automatiques #}
{% include 'components/status_badge.html.twig' with {
    status: 'actif',
    type: 'project'
} %}

{# Badge commande avec icône personnalisée #}
{% include 'components/status_badge.html.twig' with {
    status: 'signe',
    type: 'order',
    icon: 'bx-check-shield',
    size: 'lg'
} %}
```

#### empty_state.html.twig

**Fichier** : `templates/components/empty_state.html.twig`

État vide élégant avec animation, illustration, titre, description et actions.

```twig
{% include 'components/empty_state.html.twig' with {
    icon: 'bx-folder-open',
    title: 'Aucun projet',
    description: 'Vous n\'avez pas encore créé de projet.',
    action_label: 'Créer un projet',
    action_path: 'project_new',
    action_icon: 'bx-plus'
} %}
```

**Paramètres** :
- `icon` (string, requis) : Icône Boxicons principale
- `title` (string, requis) : Titre de l'état vide
- `description` (string, optionnel) : Description/message explicatif
- `action_label` (string, optionnel) : Label du bouton d'action primaire
- `action_path` (string, optionnel) : Route Symfony pour l'action primaire
- `action_icon` (string, optionnel) : Icône du bouton d'action
- `secondary_action_label` (string, optionnel) : Label du bouton secondaire
- `secondary_action_path` (string, optionnel) : Route pour l'action secondaire
- `illustration` (string, optionnel) : Type d'illustration - `folder`, `search`, `add`, `data`, `error`, `time`, `user`, `document`, `filter` (remplace `icon`)
- `size` (string, optionnel) : Taille - `sm`, `md` (défaut), `lg`

**Caractéristiques** :
- Animation de flottement sur l'icône
- Effet hover sur l'icône (changement d'opacité)
- Responsive avec max-width automatique sur la description

**Exemples** :
```twig
{# État vide simple #}
{% include 'components/empty_state.html.twig' with {
    icon: 'bx-search',
    title: 'Aucun résultat',
    description: 'Essayez avec d\'autres critères de recherche'
} %}

{# État vide avec action #}
{% include 'components/empty_state.html.twig' with {
    illustration: 'folder',
    title: 'Aucun client',
    description: 'Commencez par ajouter votre premier client.',
    action_label: 'Ajouter un client',
    action_path: 'client_new',
    action_icon: 'bx-plus',
    size: 'lg'
} %}

{# État vide avec actions multiples #}
{% include 'components/empty_state.html.twig' with {
    icon: 'bx-data',
    title: 'Aucune donnée',
    description: 'Importez des données ou créez-en manuellement.',
    action_label: 'Importer',
    action_path: 'data_import',
    secondary_action_label: 'Créer manuellement',
    secondary_action_path: 'data_new'
} %}
```

#### stats_card.html.twig

**Fichier** : `templates/components/stats_card.html.twig`

Carte de statistique/KPI avec icône, valeur, label, tendance et lien optionnel.

```twig
{% include 'components/stats_card.html.twig' with {
    value: '125 450',
    label: 'Chiffre d\'affaires',
    icon: 'bx-dollar-circle',
    color: 'success',
    suffix: '€',
    trend: 'up',
    trend_value: '+12%',
    trend_label: 'vs mois dernier'
} %}
```

**Paramètres** :
- `value` (string/int, requis) : Valeur du KPI à afficher
- `label` (string, requis) : Label du KPI
- `icon` (string, requis) : Icône Boxicons
- `color` (string, optionnel) : Couleur du thème - `primary` (défaut), `success`, `warning`, `danger`, `info`, `secondary`
- `suffix` (string, optionnel) : Suffixe après la valeur (ex: `€`, `%`, `j`)
- `prefix` (string, optionnel) : Préfixe avant la valeur (ex: `+`, `-`)
- `trend` (string, optionnel) : Tendance - `up`, `down`, `neutral`
- `trend_value` (string, optionnel) : Valeur de la tendance (ex: `+12%`, `-5%`)
- `trend_label` (string, optionnel) : Label de la tendance (ex: `vs mois dernier`)
- `link` (string, optionnel) : Route Symfony pour rendre la carte cliquable
- `link_params` (object, optionnel) : Paramètres pour la route
- `size` (string, optionnel) : Taille - `sm`, `md` (défaut), `lg`
- `custom_class` (string, optionnel) : Classes CSS additionnelles

**Caractéristiques** :
- Effet hover avec élévation (translateY + box-shadow)
- Icône dans un avatar coloré avec background subtle
- Badge de tendance coloré automatiquement (vert/rouge/gris)
- Carte entière cliquable si `link` défini
- Responsive avec flexbox

**Exemples** :
```twig
{# KPI simple #}
{% include 'components/stats_card.html.twig' with {
    value: '42',
    label: 'Projets actifs',
    icon: 'bx-folder-open',
    color: 'primary'
} %}

{# KPI avec tendance #}
{% include 'components/stats_card.html.twig' with {
    value: '85 200',
    label: 'Chiffre d\'affaires',
    icon: 'bx-euro',
    color: 'success',
    suffix: '€',
    trend: 'up',
    trend_value: '+18%',
    trend_label: 'vs année dernière'
} %}

{# KPI cliquable avec lien vers dashboard #}
{% include 'components/stats_card.html.twig' with {
    value: '2 450',
    label: 'Heures facturables',
    icon: 'bx-time',
    color: 'info',
    suffix: 'h',
    trend: 'down',
    trend_value: '-3%',
    trend_label: 'vs mois dernier',
    link: 'timesheet_index',
    size: 'md'
} %}

{# Grid de KPIs (utilisation typique) #}
<div class="row">
    <div class="col-xl-3 col-md-6">
        {% include 'components/stats_card.html.twig' with {
            value: '125 450',
            label: 'CA',
            icon: 'bx-euro',
            color: 'success',
            suffix: '€',
            trend: 'up',
            trend_value: '+12%'
        } %}
    </div>
    <div class="col-xl-3 col-md-6">
        {% include 'components/stats_card.html.twig' with {
            value: '42',
            label: 'Projets',
            icon: 'bx-folder',
            color: 'primary'
        } %}
    </div>
    {# ... autres KPIs #}
</div>
```

---

## Changelog

- **v1.2.0** (2025-12-27) : Phase 6 - Lot 9 Cohérence UX/UI (35% → 100%)
  - Nouveaux components : status_badge, empty_state, stats_card
  - Badges de statut intelligents avec couleurs et icônes automatiques
  - États vides animés avec actions multiples
  - Cartes KPI avec tendances et liens cliquables
  - 11 composants réutilisables au total
  - Documentation complète de tous les composants

- **v1.1.0** (2025-11-26) : Phase 5 - Lot 15.5 Cohérence UX/UI
  - Nouveaux components : card_section, form_buttons
  - Standards formulaires stricts (titres h5 mb-0, boutons, icônes)
  - Standards listes (pagination KnpPaginator, filtres)
  - Standards dashboards (Chart.js avec conteneurs fixes)
  - Checklists de validation
  - Documentation JavaScript modules

- **v1.0.0** (2025-11-25) : Création du design system initial
  - Composants de base
  - Templates CRUD
  - Documentation complète
