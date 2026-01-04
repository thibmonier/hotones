# RÉVISION - Analyse find() → getReference()

**Date:** 2026-01-04
**Status:** ⚠️ ANALYSE INITIALE INCORRECTE - RÉVISION NÉCESSAIRE

## ❌ Problème Identifié

L'analyse initiale par l'agent Explore a identifié 12 cas, mais n'a PAS vérifié si les entités chargées ont leurs **propriétés accédées**.

### Règle Critique

**getReference() NE PEUT PAS être utilisé si on accède aux propriétés :**
```php
// ❌ IMPOSSIBLE avec getReference()
$project = $em->getReference(Project::class, $id);
echo $project->getName(); // Déclenche SELECT lazy!

// ✅ POSSIBLE avec getReference()
$project = $em->getReference(Project::class, $id);
$task->setProject($project); // Pas d'accès propriété
```

## 🔍 Révision TimesheetController

### ❌ CAS INVALIDES (accès propriétés)

#### startTimer() - lignes 449-505
```php
// Ligne 449-452: Project find()
$project = $em->getRepository(Project::class)->find($projectId);

// Ligne 500: ACCÈS PROPRIÉTÉ!
'project' => ['id' => $project->getId(), 'name' => $project->getName()],
//                                                  ↑ Hydrate l'entité!
```
**VERDICT:** ❌ **IMPOSSIBLE** - getName() nécessite hydratation

#### startTimer() - lignes 456-505
```php
// Ligne 456-459: Task find()
$task = $em->getRepository(ProjectTask::class)->find($taskId);

// Ligne 501: ACCÈS PROPRIÉTÉ!
'task' => $task ? ['id' => $task->getId(), 'name' => $task->getName()] : null,
//                                                    ↑ Hydrate l'entité!
```
**VERDICT:** ❌ **IMPOSSIBLE** - getName() nécessite hydratation

#### startTimer() - lignes 467-505
```php
// Ligne 467-470: SubTask find()
$subTask = $em->getRepository(ProjectSubTask::class)->find($subTaskId);

// Ligne 502: ACCÈS PROPRIÉTÉ!
'subTask' => $subTask ? ['id' => $subTask->getId(), 'title' => $subTask->getTitle()] : null,
//                                                            ↑ Hydrate l'entité!
```
**VERDICT:** ❌ **IMPOSSIBLE** - getTitle() nécessite hydratation

#### save() - lignes 147-154
```php
// Ligne 147-150: Task find()
$task = $em->getRepository(ProjectTask::class)->find($taskId);

// Ligne 152: ACCÈS RELATION!
if ($task->getProject()->getId() !== $project->getId()) {
//         ↑ Hydrate la relation Project!
```
**VERDICT:** ❌ **IMPOSSIBLE** - getProject() hydrate la relation

---

### ✅ CAS VALIDES (pas d'accès propriétés)

#### 1. save() - Project (lignes 140-179)
```php
// Ligne 140-143: Project find()
$project = $em->getRepository(Project::class)->find($projectId);
if (!$project) {
    return new JsonResponse(['error' => 'Projet non trouvé'], 400);
}

// Ligne 152: Accès ID seulement (OK avec proxy)
if ($task->getProject()->getId() !== $project->getId()) {
//                                             ↑ ID connu dans proxy

// Ligne 179: Assignation relation (OK)
$timesheet->setProject($project);
```

**OPTIMISATION POSSIBLE:**
```php
// Solution: Validation différée au flush()
$project = $em->getReference(Project::class, $projectId);
$timesheet->setProject($project);

try {
    $em->flush();
} catch (EntityNotFoundException $e) {
    return new JsonResponse(['error' => 'Projet non trouvé'], 400);
}
```

**VERDICT:** ✅ **POSSIBLE** (avec gestion erreur adaptée)

---

#### 2. exportPdf() - Project (lignes 810-812)
```php
// Ligne 811: Project find()
$project = $em->getRepository(Project::class)->find($projectId);

// Ligne 812: Jamais utilisé! Juste comparaison ID sur autre entité
$timesheets = array_filter($timesheets, fn ($t) => $t->getProject()->getId() === (int) $projectId);
//                                               ↑ Utilise $projectId, PAS $project!
```

**OPTIMISATION POSSIBLE:**
```php
// Le find() est inutile, supprimer complètement!
// Ligne 811: Supprimer
// Ligne 812: Déjà utilise $projectId
```

**VERDICT:** ✅ **SUPPRESSION COMPLÈTE** - Variable $project jamais utilisée!

---

## 📊 Révision Autres Controllers

### ✅ CAS VALIDES (4 optimisations confirmées)

#### 1. OrderController::addLine() - Profile (ligne 347)
```php
// Ligne 347: Profile find()
$profile = $em->getRepository(Profile::class)->find($profileId);

// Ligne 349: Assignation uniquement
if ($profile) {
    $line->setProfile($profile);
}
```
**VERDICT:** ✅ **VALIDE** - Seulement assignation relation

---

#### 2. OrderController::editLine() - Profile (ligne 403)
```php
// Ligne 403: Profile find()
$profile = $em->getRepository(Profile::class)->find($profileId);

// Ligne 405: Assignation uniquement
if ($profile) {
    $line->setProfile($profile);
}
```
**VERDICT:** ✅ **VALIDE** - Seulement assignation relation

---

#### 3. ProjectSubTaskController::updatePositions() - ProjectSubTask (ligne 60)
```php
// Ligne 60: ProjectSubTask find() dans boucle
foreach ($positions as $id => $pos) {
    $st = $this->em->getRepository(ProjectSubTask::class)->find((int) $id);
    if ($st) {
        $st->setPosition((int) $pos);  // ← Setter uniquement
    }
}
```
**VERDICT:** ✅ **VALIDE** - Setter uniquement

**IMPACT IMPORTANT:** N requêtes SELECT éliminées (N = nombre sous-tâches)

---

#### 4. InvoiceController::index() - Client/Project (lignes 53-54)
```php
// Lignes 53-54: Client/Project find()
$client  = $clientId ? $em->getRepository(Client::class)->find($clientId) : null;
$project = $projectId ? $em->getRepository(Project::class)->find($projectId) : null;

// Lignes 74, 77: QueryBuilder accepte les proxies
if ($client) {
    $qb->andWhere('i.client = :client')->setParameter('client', $client);
}
if ($project) {
    $qb->andWhere('i.project = :project')->setParameter('project', $project);
}
```
**VERDICT:** ✅ **VALIDE** - QueryBuilder accepte les proxies Doctrine

---

### ❌ CAS INVALIDES (2 rejets confirmés)

#### ProjectTaskController::new() - Project (ligne 55)
```php
// Ligne 55: Project find()
$project = $this->em->getRepository(Project::class)->find($projectId);

// Ligne 61: ACCÈS RELATION!
$task->setCompany($project->getCompany());
//                        ↑ Hydrate la relation Company!
```
**VERDICT:** ❌ **IMPOSSIBLE** - getCompany() nécessite hydratation

---

#### ContributorSkillController::new() - Contributor (ligne 51)
```php
// Ligne 51: Contributor find()
$contributor = $this->em->getRepository(Contributor::class)->find($contributorId);

// Ligne 57: ACCÈS RELATION!
$contributorSkill->setCompany($contributor->getCompany());
//                                        ↑ Hydrate la relation Company!
```
**VERDICT:** ❌ **IMPOSSIBLE** - getCompany() nécessite hydratation

---

## 🎯 Révision Objectifs

### Optimisations Confirmées

**TimesheetController (2 cas validés) :**
1. ✅ save() - Project (ligne 140) → getReference() avec gestion erreur
2. ✅ exportPdf() - Project (ligne 811) → **SUPPRIMER** (variable inutile)

**OrderController (2 cas validés) :**
3. ✅ addLine() - Profile (ligne 347) → getReference()
4. ✅ editLine() - Profile (ligne 403) → getReference()

**ProjectSubTaskController (1 cas validé - IMPACT FORT) :**
5. ✅ updatePositions() - ProjectSubTask (ligne 60) → getReference() dans boucle

**InvoiceController (1 cas validé) :**
6. ✅ index() - Client/Project (lignes 53-54) → getReference()

**REJETS (2 cas invalides) :**
- ❌ ProjectTaskController::new() - accède à getCompany()
- ❌ ContributorSkillController::new() - accède à getCompany()

### Gain de Performance Final

**Total validé:** 5 optimisations + 1 suppression = **6 modifications**

**Requêtes éliminées par scénario typique :**
- TimesheetController::save() : -1 SELECT (par soumission timesheet)
- OrderController::addLine/editLine() : -1 SELECT (par modification ligne devis)
- **ProjectSubTaskController::updatePositions() : -N SELECT** (N = sous-tâches déplacées, souvent 5-20)
- InvoiceController::index() : -1 ou -2 SELECT (si filtres actifs)

**Impact total estimé : -5 à -25 requêtes selon contexte**

**Cas le plus impactant:** updatePositions() peut éliminer 10-20 requêtes d'un seul coup lors du drag & drop de sous-tâches !

---

## 📋 Plan d'Implémentation

### Phase 1: TimesheetController (2 modifications)

1. **save() - Project (ligne 140)**
   ```php
   // AVANT
   $project = $em->getRepository(Project::class)->find($projectId);
   if (!$project) {
       return new JsonResponse(['error' => 'Projet non trouvé'], 400);
   }

   // APRÈS
   $project = $em->getReference(Project::class, $projectId);
   // Validation différée au flush()
   ```

2. **exportPdf() - Project (ligne 811) - SUPPRESSION**
   ```php
   // AVANT
   $project = null;
   if ($projectId) {
       $project = $em->getRepository(Project::class)->find($projectId);
   }
   // $project jamais utilisé après!

   // APRÈS
   // Supprimer complètement ces 3 lignes
   ```

---

### Phase 2: OrderController (2 modifications)

3. **addLine() - Profile (ligne 347)**
   ```php
   // AVANT
   if ($profileId = $request->request->get('profile_id')) {
       $profile = $em->getRepository(Profile::class)->find($profileId);
       if ($profile) {
           $line->setProfile($profile);
       }
   }

   // APRÈS
   if ($profileId = $request->request->get('profile_id')) {
       $line->setProfile($em->getReference(Profile::class, $profileId));
   }
   ```

4. **editLine() - Profile (ligne 403) - IDENTIQUE**

---

### Phase 3: ProjectSubTaskController (1 modification - PRIORITAIRE)

5. **updatePositions() - ProjectSubTask (ligne 60)**
   ```php
   // AVANT
   foreach ($positions as $id => $pos) {
       $st = $this->em->getRepository(ProjectSubTask::class)->find((int) $id);
       if ($st) {
           $st->setPosition((int) $pos);
       }
   }

   // APRÈS
   foreach ($positions as $id => $pos) {
       $st = $this->em->getReference(ProjectSubTask::class, (int) $id);
       $st->setPosition((int) $pos);
   }
   // Doctrine détectera automatiquement les IDs invalides au flush()
   ```

---

### Phase 4: InvoiceController (1 modification)

6. **index() - Client/Project (lignes 53-54)**
   ```php
   // AVANT
   $client  = $clientId ? $em->getRepository(Client::class)->find($clientId) : null;
   $project = $projectId ? $em->getRepository(Project::class)->find($projectId) : null;

   // APRÈS
   $client  = $clientId ? $em->getReference(Client::class, $clientId) : null;
   $project = $projectId ? $em->getReference(Project::class, $projectId) : null;
   ```

---

**Conclusion:** Validation manuelle terminée. 6 cas confirmés sur 12 initiaux (50%). Prêt pour implémentation !
