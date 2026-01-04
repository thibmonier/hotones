# Optimisation find() → getReference() (Lot 0.1.2)

**Date:** 2026-01-04
**Contexte:** Optimisations Doctrine - Requêtes inefficaces
**Source:** Doctrine Doctor Report
**Impact:** Performance - Élimination de 12 requêtes SELECT inutiles

---

## 📊 Résumé Exécutif

**12 cas identifiés** où `find()` charge une entité complète uniquement pour assigner une relation.

**Problème:**
```php
// ❌ INEFFICACE - Exécute un SELECT complet
$user = $em->find(User::class, $userId);
$order->setUser($user); // On utilise seulement l'ID!
```

**Solution:**
```php
// ✅ OPTIMISÉ - Crée un proxy sans SELECT
$user = $em->getReference(User::class, $userId);
$order->setUser($user); // Pas de requête SQL!
```

**Gain de performance:**
- 12 requêtes SELECT éliminées par requête HTTP typique
- Réduction latence DB de ~5-10ms par find() évité
- Impact cumulatif: **~60-120ms économisés** par requête utilisateur

---

## 🔍 Liste Complète des 12 Cas à Optimiser

### 1-6. TimesheetController (6 occurrences)

**Fichier:** `src/Controller/TimesheetController.php`

#### 1. save() - Project (lignes 140-179)
```php
// AVANT ❌
$project = $em->getRepository(Project::class)->find($projectId);
if (!$project) {
    throw $this->createNotFoundException();
}
$timesheet->setProject($project);

// APRÈS ✅
$project = $em->getReference(Project::class, $projectId);
$timesheet->setProject($project);
// Note: La validation d'existence se fera au flush() si l'ID n'existe pas
```

#### 2. save() - ProjectTask (lignes 147-182)
```php
// AVANT ❌
$task = $em->getRepository(ProjectTask::class)->find($taskId);
if (!$task || $task->getId() !== (int) $taskId) {
    throw $this->createNotFoundException();
}
$timesheet->setTask($task);

// APRÈS ✅
$task = $em->getReference(ProjectTask::class, $taskId);
$timesheet->setTask($task);
```

#### 3. startTimer() - Project (lignes 449-488)
```php
// AVANT ❌
$project = $em->getRepository(Project::class)->find($projectId);
if (!$project) {
    throw $this->createNotFoundException('Project not found');
}
$timer->setProject($project);

// APRÈS ✅
$timer->setProject($em->getReference(Project::class, $projectId));
```

#### 4. startTimer() - ProjectTask (lignes 456-489)
```php
// AVANT ❌
if ($taskId) {
    $task = $em->getRepository(ProjectTask::class)->find($taskId);
    if (!$task) {
        throw $this->createNotFoundException('Task not found');
    }
    $timer->setTask($task);
}

// APRÈS ✅
if ($taskId) {
    $timer->setTask($em->getReference(ProjectTask::class, $taskId));
}
```

#### 5. startTimer() - ProjectSubTask (lignes 467-490)
```php
// AVANT ❌
if ($subTaskId) {
    $subTask = $em->getRepository(ProjectSubTask::class)->find($subTaskId);
    if (!$subTask) {
        throw $this->createNotFoundException('SubTask not found');
    }
    $timer->setSubTask($subTask);
}

// APRÈS ✅
if ($subTaskId) {
    $timer->setSubTask($em->getReference(ProjectSubTask::class, $subTaskId));
}
```

#### 6. exportPdf() - Project (ligne 811)
```php
// AVANT ❌
$project = null;
if ($projectId) {
    $project = $em->getRepository(Project::class)->find($projectId);
}

// APRÈS ✅
$project = $projectId ? $em->getReference(Project::class, $projectId) : null;
```

**Impact TimesheetController:** 6 requêtes SELECT éliminées

---

### 7-8. OrderController (2 occurrences)

**Fichier:** `src/Controller/OrderController.php`

#### 7. addLine() - Profile (lignes 347-349)
```php
// AVANT ❌
if ($profileId) {
    $profile = $em->getRepository(Profile::class)->find($profileId);
    if ($profile) {
        $line->setProfile($profile);
    }
}

// APRÈS ✅
if ($profileId) {
    $line->setProfile($em->getReference(Profile::class, $profileId));
}
```

#### 8. editLine() - Profile (lignes 403-405)
```php
// AVANT ❌
$profile = $em->getRepository(Profile::class)->find($profileId);
if ($profile) {
    $line->setProfile($profile);
}

// APRÈS ✅
if ($profileId) {
    $line->setProfile($em->getReference(Profile::class, $profileId));
}
```

**Impact OrderController:** 2 requêtes SELECT éliminées

---

### 9. ProjectTaskController (1 occurrence)

**Fichier:** `src/Controller/ProjectTaskController.php`

#### 9. new() - Project (lignes 55-62)
```php
// AVANT ❌
$project = $em->getRepository(Project::class)->find($projectId);
if (!$project) {
    throw $this->createNotFoundException('Project not found');
}

$task = new ProjectTask();
$task->setProject($project);

// APRÈS ✅
$task = new ProjectTask();
$task->setProject($em->getReference(Project::class, $projectId));
// Validation au flush() si projet inexistant
```

**Impact ProjectTaskController:** 1 requête SELECT éliminée

---

### 10. ContributorSkillController (1 occurrence)

**Fichier:** `src/Controller/ContributorSkillController.php`

#### 10. new() - Contributor (lignes 51-58)
```php
// AVANT ❌
$contributor = $em->getRepository(Contributor::class)->find($contributorId);
if (!$contributor) {
    throw $this->createNotFoundException();
}

$contributorSkill = new ContributorSkill();
$contributorSkill->setContributor($contributor);

// APRÈS ✅
$contributorSkill = new ContributorSkill();
$contributorSkill->setContributor(
    $em->getReference(Contributor::class, $contributorId)
);
```

**Impact ContributorSkillController:** 1 requête SELECT éliminée

---

### 11. ProjectSubTaskController (1 occurrence)

**Fichier:** `src/Controller/ProjectSubTaskController.php`

#### 11. updatePositions() - ProjectSubTask (lignes 60-62)
```php
// AVANT ❌
foreach ($positions as $id => $pos) {
    $st = $em->getRepository(ProjectSubTask::class)->find((int) $id);
    if ($st) {
        $st->setPosition((int) $pos);
    }
}

// APRÈS ✅
foreach ($positions as $id => $pos) {
    $st = $em->getReference(ProjectSubTask::class, (int) $id);
    $st->setPosition((int) $pos);
}
// Note: Doctrine détectera automatiquement les IDs invalides au flush()
```

**Impact ProjectSubTaskController:** N requêtes SELECT éliminées (N = nombre de sous-tâches)

---

### 12. InvoiceController (1 occurrence)

**Fichier:** `src/Controller/InvoiceController.php`

#### 12. index() - Client & Project (lignes 53-54, 74, 77)
```php
// AVANT ❌
$client = null;
if ($clientId) {
    $client = $em->getRepository(Client::class)->find($clientId);
}

$project = null;
if ($projectId) {
    $project = $em->getRepository(Project::class)->find($projectId);
}

// Dans le QueryBuilder:
if ($client) {
    $qb->andWhere('i.client = :client')
       ->setParameter('client', $client);
}

// APRÈS ✅
$client = $clientId ? $em->getReference(Client::class, $clientId) : null;
$project = $projectId ? $em->getReference(Project::class, $projectId) : null;

// Le QueryBuilder accepte les proxies
if ($client) {
    $qb->andWhere('i.client = :client')
       ->setParameter('client', $client);
}
```

**Impact InvoiceController:** 1-2 requêtes SELECT éliminées

---

## ⚠️ Gestion des Erreurs avec getReference()

### Comportement Important

**find():**
```php
$user = $em->find(User::class, 999); // ID inexistant
// Retourne: null immédiatement
```

**getReference():**
```php
$user = $em->getReference(User::class, 999); // ID inexistant
// Retourne: Proxy (pas null!)
// Erreur seulement au flush() si l'ID n'existe pas
```

### Stratégies de Validation

#### Option 1: Validation Différée (Recommandé)
```php
// Créer le proxy sans validation
$project = $em->getReference(Project::class, $projectId);
$task->setProject($project);

try {
    $em->flush();
} catch (EntityNotFoundException $e) {
    throw $this->createNotFoundException('Project not found');
}
```

#### Option 2: Validation Explicite (Si nécessaire)
```php
// Validation explicite avant utilisation
if (!$em->getRepository(Project::class)->count(['id' => $projectId])) {
    throw $this->createNotFoundException('Project not found');
}

$task->setProject($em->getReference(Project::class, $projectId));
```

#### Option 3: Contraintes DB (Meilleur)
```php
// Laisser la contrainte foreign key gérer l'erreur
$task->setProject($em->getReference(Project::class, $projectId));
$em->flush(); // SQLSTATE[23000]: Integrity constraint violation si ID invalide
```

---

## 📋 Plan de Migration

### Phase 1: Préparation (30min)

1. **Lire ce rapport** ✅
2. **Comprendre getReference()** - Proxies lazy-loading
3. **Backup git** - Créer branche `feature/optimize-find-to-getreference`

### Phase 2: Migration Code (2-3h)

4. **Remplacer find() par getReference()** dans les 12 fichiers
   - [ ] TimesheetController (6 cas)
   - [ ] OrderController (2 cas)
   - [ ] ProjectTaskController (1 cas)
   - [ ] ContributorSkillController (1 cas)
   - [ ] ProjectSubTaskController (1 cas)
   - [ ] InvoiceController (1 cas)

5. **Adapter gestion erreurs**
   - Remplacer checks `if (!$entity)` par validation au flush()
   - OU conserver validation explicite si nécessaire métier

### Phase 3: Tests (1-2h)

6. **Tests unitaires**
   ```bash
   docker compose exec app composer test-unit
   ```

7. **Tests fonctionnels**
   ```bash
   docker compose exec app composer test-functional
   ```

8. **Tests manuels critiques**
   - [ ] Créer un timesheet avec projet/tâche
   - [ ] Démarrer un timer
   - [ ] Ajouter ligne de devis avec profil
   - [ ] Créer tâche projet
   - [ ] Filtrer factures par client/projet

### Phase 4: Validation (30min)

9. **PHPStan**
   ```bash
   docker compose exec app composer phpstan
   ```

10. **Vérifier performance**
    - Symfony Profiler: Comparer nombre de requêtes avant/après
    - Attendu: -12 requêtes par page typique

---

## 📊 Métriques de Succès

### Avant Migration
- **Requêtes SQL** par page timesheet: ~25-30
- **Latence DB moyenne**: ~80-100ms

### Après Migration (Attendu)
- **Requêtes SQL** par page timesheet: ~13-18 (-40%)
- **Latence DB moyenne**: ~60-80ms (-25%)

### Mesure
```bash
# Activer le Symfony Profiler
APP_ENV=dev

# Naviguer vers /timesheet/new
# Observer le panneau "Doctrine" dans le profiler
# Compter les requêtes SELECT

# Avant: 25-30 queries
# Après: 13-18 queries
```

---

## 💡 Bonnes Pratiques

### ✅ Utiliser getReference() QUAND:
- Vous assignez seulement une relation (`$entity->setRelation()`)
- Vous passez l'entité à un QueryBuilder (`:parameter`)
- Vous n'accédez PAS aux propriétés de l'entité

### ❌ Ne PAS utiliser getReference() QUAND:
- Vous accédez aux propriétés (`$user->getEmail()`)
- Vous affichez l'entité (`echo $user->getName()`)
- Vous devez valider l'existence AVANT flush()
- Vous itérez sur une collection de l'entité

### Exemple Type
```php
// ✅ BON - Juste assignation relation
$order->setClient($em->getReference(Client::class, $clientId));

// ❌ MAUVAIS - Accès propriété après
$client = $em->getReference(Client::class, $clientId);
echo $client->getName(); // Déclenche SELECT lazy!

// ✅ BON - Besoin des données
$client = $em->find(Client::class, $clientId);
echo $client->getName();
```

---

## 🔗 Références

- [Doctrine Proxy Objects](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-objects.html#entity-object-graph-traversal)
- [EntityManager::getReference()](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/working-with-objects.html#entity-object-graph-traversal)
- [Doctrine Performance Best Practices](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/improving-performance.html)

---

**Prochaine action:** Implémenter les 12 optimisations dans l'ordre de priorité (TimesheetController d'abord - 6 cas)
