# Dependent Fields Helper (Form Cascades)

## Vue d'ensemble

Le système de champs dépendants (dependent fields) permet de créer des listes déroulantes en cascade, où les options d'un champ dépendent de la valeur sélectionnée dans un autre champ.

Cas d'usage typiques :
- **Projet → Tâches** : Sélectionner un projet charge ses tâches
- **Client → Projets** : Sélectionner un client charge ses projets
- **Pays → Villes** : Sélectionner un pays charge ses villes
- **Catégorie → Sous-catégories** : Cascade de catégories

## Fonctionnalités

- ✅ Configuration déclarative via attributs HTML `data-*`
- ✅ Chargement asynchrone via API
- ✅ États visuels (loading, error)
- ✅ Support de multiples niveaux de cascade (A → B → C)
- ✅ Restauration de valeur sur chargement initial
- ✅ Déclenchement d'événements `change` pour compatibilité
- ✅ API JavaScript pour création programmatique
- ✅ Auto-initialisation sur DOMContentLoaded

## Installation

### 1. Inclure le Script

Le script est compilé via Webpack Encore. Ajoutez-le dans votre template Twig :

```twig
{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('assets/dependent-fields.js') }}"></script>
{% endblock %}
```

## Utilisation Basique

### 1. Markup HTML

Ajoutez les attributs `data-dependent-*` au champ dépendant :

```html
<!-- Source field -->
<div class="mb-3">
    <label for="project" class="form-label">Projet *</label>
    <select id="project" name="project" class="form-select" required>
        <option value="">Sélectionner un projet...</option>
        <option value="1">Projet A</option>
        <option value="2">Projet B</option>
    </select>
</div>

<!-- Dependent field -->
<div class="mb-3">
    <label for="task" class="form-label">Tâche *</label>
    <select
        id="task"
        name="task"
        class="form-select"
        data-dependent-source="#project"
        data-dependent-url="/api/projects/{value}/tasks"
        data-dependent-placeholder="Sélectionner une tâche..."
        data-dependent-on-load="true"
        required
    >
        <option value="">Sélectionner un projet d'abord</option>
    </select>
</div>
```

### 2. Créer l'Endpoint API

Le endpoint doit retourner un tableau JSON avec les options :

```php
#[Route('/api/projects/{id}/tasks', name: 'api_project_tasks', methods: ['GET'])]
public function getProjectTasks(int $id): JsonResponse
{
    $tasks = $this->projectTaskRepository->findBy(
        ['project' => $id, 'active' => true],
        ['position' => 'ASC']
    );

    $data = array_map(fn($task) => [
        'id'   => $task->getId(),
        'name' => $task->getName(),
    ], $tasks);

    return new JsonResponse($data);
}
```

**Format de réponse attendu :**
```json
[
  {"id": 1, "name": "Tâche A"},
  {"id": 2, "name": "Tâche B"},
  {"id": 3, "name": "Tâche C"}
]
```

## Attributs de Configuration

### `data-dependent-source` (Requis)

Sélecteur CSS du champ source (parent).

```html
data-dependent-source="#project"
data-dependent-source=".client-select"
data-dependent-source="[name='country']"
```

### `data-dependent-url` (Requis)

URL de l'API pour récupérer les options. Utilisez `{value}` comme placeholder pour la valeur du champ source.

```html
data-dependent-url="/api/projects/{value}/tasks"
data-dependent-url="/api/clients/{value}/projects"
data-dependent-url="/countries/{value}/cities"
```

### `data-dependent-placeholder` (Optionnel)

Texte du placeholder (option vide).

```html
data-dependent-placeholder="Sélectionner une tâche..."
```

**Défaut :** `"Select..."`

### `data-dependent-on-load` (Optionnel)

Si `"true"`, charge les options au chargement de la page si le champ source a déjà une valeur.

```html
data-dependent-on-load="true"
```

**Défaut :** `false`

Utile pour les formulaires d'édition où les valeurs sont pré-remplies.

### `data-dependent-value-field` (Optionnel)

Nom du champ à utiliser pour la valeur de l'option.

```html
data-dependent-value-field="id"
```

**Défaut :** `"id"`

### `data-dependent-label-field` (Optionnel)

Nom du champ à utiliser pour le label de l'option.

```html
data-dependent-label-field="name"
```

**Défaut :** `"name"`

## Exemples Complets

### Exemple 1 : Projet → Tâche (Timesheet)

```twig
{# templates/timesheet/_form.html.twig #}
<form method="post">
    <div class="row">
        <div class="col-md-6">
            <div class="mb-3">
                <label for="timesheet_project" class="form-label">Projet *</label>
                <select id="timesheet_project" name="timesheet[project]" class="form-select" required>
                    <option value="">Sélectionner un projet...</option>
                    {% for project in projects %}
                        <option value="{{ project.id }}" {% if timesheet.project and timesheet.project.id == project.id %}selected{% endif %}>
                            {{ project.name }}
                        </option>
                    {% endfor %}
                </select>
            </div>
        </div>

        <div class="col-md-6">
            <div class="mb-3">
                <label for="timesheet_task" class="form-label">Tâche</label>
                <select
                    id="timesheet_task"
                    name="timesheet[task]"
                    class="form-select"
                    data-dependent-source="#timesheet_project"
                    data-dependent-url="/api/projects/{value}/tasks"
                    data-dependent-placeholder="Sélectionner une tâche..."
                    data-dependent-on-load="true"
                >
                    <option value="">Sélectionner un projet d'abord</option>
                </select>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">Enregistrer</button>
</form>

{% block javascripts %}
    {{ parent() }}
    <script src="{{ asset('assets/dependent-fields.js') }}"></script>
{% endblock %}
```

### Exemple 2 : Client → Projet → Tâche (Triple Cascade)

```html
<!-- Niveau 1 : Client -->
<select id="client" name="client" class="form-select">
    <option value="">Sélectionner un client...</option>
    <option value="1">Client A</option>
    <option value="2">Client B</option>
</select>

<!-- Niveau 2 : Projet (dépend de Client) -->
<select
    id="project"
    name="project"
    class="form-select"
    data-dependent-source="#client"
    data-dependent-url="/api/clients/{value}/projects"
    data-dependent-placeholder="Sélectionner un projet..."
>
    <option value="">Sélectionner un client d'abord</option>
</select>

<!-- Niveau 3 : Tâche (dépend de Projet) -->
<select
    id="task"
    name="task"
    class="form-select"
    data-dependent-source="#project"
    data-dependent-url="/api/projects/{value}/tasks"
    data-dependent-placeholder="Sélectionner une tâche..."
>
    <option value="">Sélectionner un projet d'abord</option>
</select>
```

### Exemple 3 : API Controller pour les Cascades

```php
<?php

namespace App\Controller\Api;

use App\Repository\ProjectRepository;
use App\Repository\ProjectTaskRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api')]
#[IsGranted('ROLE_USER')]
class DependentFieldsController extends AbstractController
{
    /**
     * Get active projects for a client
     */
    #[Route('/clients/{id}/projects', name: 'api_client_projects', methods: ['GET'])]
    public function getClientProjects(
        int $id,
        ProjectRepository $projectRepository
    ): JsonResponse {
        $projects = $projectRepository->findBy(
            ['client' => $id, 'active' => true],
            ['name' => 'ASC']
        );

        $data = array_map(fn($project) => [
            'id'   => $project->getId(),
            'name' => $project->getName(),
        ], $projects);

        return new JsonResponse($data);
    }

    /**
     * Get active tasks for a project
     */
    #[Route('/projects/{id}/tasks', name: 'api_project_tasks', methods: ['GET'])]
    public function getProjectTasks(
        int $id,
        ProjectTaskRepository $taskRepository
    ): JsonResponse {
        $tasks = $taskRepository->findBy(
            ['project' => $id, 'active' => true],
            ['position' => 'ASC']
        );

        $data = array_map(fn($task) => [
            'id'          => $task->getId(),
            'name'        => $task->getName(),
            'description' => $task->getDescription(),
        ], $tasks);

        return new JsonResponse($data);
    }

    /**
     * Get subtasks for a task
     */
    #[Route('/tasks/{id}/subtasks', name: 'api_task_subtasks', methods: ['GET'])]
    public function getTaskSubtasks(
        int $id,
        ProjectSubTaskRepository $subTaskRepository
    ): JsonResponse {
        $subTasks = $subTaskRepository->findBy(
            ['task' => $id],
            ['position' => 'ASC']
        );

        $data = array_map(fn($subTask) => [
            'id'    => $subTask->getId(),
            'name'  => $subTask->getTitle(),
        ], $subTasks);

        return new JsonResponse($data);
    }
}
```

## API JavaScript

### Initialisation Manuelle

Si vous devez initialiser manuellement (par exemple après chargement AJAX) :

```javascript
import { initDependentFields } from './dependent-fields.js';

// Initialiser tous les champs dépendants de la page
initDependentFields();
```

### Création Programmatique

Pour créer une relation de dépendance en JavaScript :

```javascript
import { createDependentField } from './dependent-fields.js';

const sourceField = document.getElementById('project');
const dependentField = document.getElementById('task');

const dependentFieldInstance = createDependentField(sourceField, dependentField, {
    url: '/api/projects/{value}/tasks',
    placeholder: 'Sélectionner une tâche...',
    loadOnInit: true,
    valueField: 'id',
    labelField: 'name'
});
```

## Configuration Avancée

### Personnaliser les Champs Value/Label

Si votre API retourne des champs avec des noms différents :

```json
[
  {"task_id": 1, "task_name": "Développement"},
  {"task_id": 2, "task_name": "Design"}
]
```

Configurez les attributs `data-dependent-value-field` et `data-dependent-label-field` :

```html
<select
    data-dependent-source="#project"
    data-dependent-url="/api/projects/{value}/tasks"
    data-dependent-value-field="task_id"
    data-dependent-label-field="task_name"
>
</select>
```

### Écouter les Changements

Le champ dépendant déclenche un événement `change` après chargement des options :

```javascript
const taskSelect = document.getElementById('task');

taskSelect.addEventListener('change', function() {
    console.log('Task changed to:', this.value);
});
```

### États Visuels

Le champ dépendant reçoit automatiquement des classes CSS :

- `.is-loading` : Pendant le chargement des options
- Attribut `disabled` : Quand le champ source est vide ou pendant le chargement

Vous pouvez styliser ces états :

```css
select.is-loading {
    background-image: url('/assets/images/spinner.gif');
    background-repeat: no-repeat;
    background-position: right 10px center;
}
```

## Bonnes Pratiques

1. **Toujours valider côté serveur** : La validation JavaScript peut être contournée
2. **Sécuriser les endpoints** : Utilisez `#[IsGranted('ROLE_USER')]` sur les API
3. **Limiter les résultats** : Ne retournez que les entités actives et pertinentes
4. **Gestion des erreurs** : Affichez un message clair si le chargement échoue
5. **Performance** : Ajoutez des index sur les colonnes utilisées dans les filtres SQL

## Dépannage

### Les options ne se chargent pas

- Vérifiez que le script `dependent-fields.js` est bien inclus
- Ouvrez la console navigateur pour voir les erreurs réseau
- Vérifiez que l'URL de l'API est correcte et accessible
- Vérifiez que l'utilisateur est authentifié (si l'endpoint requiert auth)

### Erreur 404 sur l'endpoint

Vérifiez que la route existe dans votre routeur :

```bash
php bin/console debug:router | grep api
```

### Les valeurs ne sont pas restaurées en mode édition

Assurez-vous que `data-dependent-on-load="true"` est présent sur le champ dépendant.

### Format de réponse incorrect

Le script attend un tableau d'objets avec au minimum les champs configurés dans `valueField` et `labelField` (par défaut `id` et `name`).

## Exemples Réels dans le Projet

- **Timesheet** : Projet → Tâche → Sous-tâche
- **Plus à venir...**
