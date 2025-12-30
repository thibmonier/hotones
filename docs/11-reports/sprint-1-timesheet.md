# 🏃 Sprint 1 : Saisie des Temps - Grille Hebdomadaire

> Sprint 1 de la Phase 1 - Durée : 2 semaines (10 jours)
>
> Objectif : Finaliser l'interface de saisie hebdomadaire production-ready

## Liens
- Plan d'exécution complet : [docs/execution-plan-2025.md](./execution-plan-2025.md)
- Roadmap 2025 : [docs/roadmap-2025.md](./roadmap-2025.md)

---

## 📊 État des lieux

### ✅ Déjà implémenté

#### Entité & Base de données
- ✅ Entité `Timesheet` complète (src/Entity/Timesheet.php:34)
  - Relations : Contributor, Project, ProjectTask (optionnel), ProjectSubTask (optionnel)
  - Champs : date, hours (decimal 5,2), notes (text)
  - Index sur project_id, contributor_id, date
  - API Platform configuré

#### Controller & Routes
- ✅ `TimesheetController` (src/Controller/TimesheetController.php:22)
  - **Route `/timesheet`** : Grille hebdomadaire de base (index:24)
  - **Route `/timesheet/save`** (POST) : Auto-save AJAX (save:109)
  - **Route `/timesheet/my-time`** : Vue mensuelle personnelle (myTime:172)
  - **Route `/timesheet/all`** : Vue tous les temps (chefs de projet) (all:207)
  - **Route `/timesheet/timer/start`** (POST) : Démarrer timer (startTimer:270)
  - **Route `/timesheet/timer/stop`** (POST) : Arrêter timer (stopTimer:341)
  - **Route `/timesheet/timer/options`** (GET) : Liste projets/tâches (timerOptions:360)
  - **Route `/timesheet/timer/active`** (GET) : Timer actif (activeTimer:404)

#### Repository
- ✅ `TimesheetRepository` très complet (src/Repository/TimesheetRepository.php:20)
  - `findByContributorAndDateRange()` (30)
  - `findForPeriodWithProject()` (61)
  - `getHoursGroupedByProjectForContributor()` (124)
  - `findExistingTimesheetWithTask()` (203)
  - `findExistingTimesheetWithTaskAndSubTask()` (171)
  - `getStatsPerContributor()` (211)
  - `getMonthlyHoursForProject()` (275)
  - Beaucoup d'autres méthodes d'agrégation

#### Fonctionnalités
- ✅ Grille hebdomadaire avec navigation semaine précédente/suivante
- ✅ Auto-save AJAX (route /timesheet/save)
- ✅ Sélection projet → tâche (cascade)
- ✅ Timer start/stop avec imputation automatique (min 1h)
- ✅ Support sous-tâches (ProjectSubTask)
- ✅ Historique personnel mensuel
- ✅ Vue globale pour chefs de projet avec filtres
- Permettre de masquer les samedis et dimanches en mettant une option d'affichage des week-ends (comme pour passage en affichage par jour)
- l'écran de saisie dit maximum 24h par jour, attention, la conversion dit 1j=8h, ca peut etre plus (heures supplémentaires) mais la norme reste nb heures travaillées par semaine / nombre de jours travaillés par semaine (ex. pour qqun au 32h, le travail est étalé sur 4j soit 8h par jour ou pour quelqu'un au 35h sur 5j = 7h par jour)

---

## 🎯 Objectifs Sprint 1

### Améliorations à apporter

#### 1. Validation des données ⭐ Priorité Haute
**Fichier** : `src/Controller/TimesheetController.php` (méthode save:109)

**À implémenter** :
- ✅ Validation max 24h/jour par contributeur
  - Vérifier la somme des heures du jour avant sauvegarde
  - Retourner erreur JSON si dépassement
- ✅ Validation min 0.125j (1h) sur l'interface (déjà en place côté timer, à ajouter côté grille manuelle)
- ✅ Validation heures > 0 (déjà en place ligne 156)

**Code à ajouter** :
```php
// Dans TimesheetController::save()
// Après récupération de $hours (ligne 123)

// Vérifier total heures du jour
$dailyTotal = $timesheetRepo->getTotalHoursForContributorAndDate($contributor, $date);
if ($dailyTotal + $hours > 24) {
    return new JsonResponse([
        'error' => sprintf(
            'Dépassement du total quotidien : %.2fh déjà saisi(es), +%.2fh = %.2fh/24h',
            $dailyTotal,
            $hours,
            $dailyTotal + $hours
        )
    ], 400);
}

// Validation minimum 0.125j (1h) si manuel
if ($hours > 0 && $hours < 1.0) {
    return new JsonResponse([
        'error' => 'La saisie minimale est de 1 heure (0.125 jour)',
    ], 400);
}
```

**Nouvelle méthode repository** :
```php
// Dans TimesheetRepository
public function getTotalHoursForContributorAndDate(
    Contributor $contributor,
    DateTimeInterface $date,
    ?Timesheet $exclude = null
): float {
    $qb = $this->createQueryBuilder('t')
        ->select('COALESCE(SUM(t.hours), 0)')
        ->where('t.contributor = :contributor')
        ->andWhere('t.date = :date')
        ->setParameter('contributor', $contributor)
        ->setParameter('date', $date);

    if ($exclude && $exclude->getId()) {
        $qb->andWhere('t.id != :excludeId')
           ->setParameter('excludeId', $exclude->getId());
    }

    return (float) $qb->getQuery()->getSingleScalarResult();
}
```

---

#### 2. Conversion Heures ↔ Jours ⭐ Priorité Moyenne
**Fichier** : `templates/timesheet/index.html.twig`

**À implémenter** :
- Toggle bouton "Heures" / "Jours" dans l'interface
- Conversion automatique : 1 jour = 8 heures
- Sauvegarde toujours en heures (backend inchangé)
- JavaScript pour gestion du toggle

**Code JavaScript** :
```javascript
// timesheet.js
let displayMode = 'hours'; // ou 'days'

function toggleDisplayMode() {
    displayMode = displayMode === 'hours' ? 'days' : 'hours';
    updateAllCells();

    // Mettre à jour le texte du bouton
    const btn = document.getElementById('toggle-mode-btn');
    btn.textContent = displayMode === 'hours' ? 'Afficher en jours' : 'Afficher en heures';
}

function updateAllCells() {
    document.querySelectorAll('.timesheet-cell').forEach(cell => {
        const hours = parseFloat(cell.dataset.hours) || 0;
        const displayed = displayMode === 'days' ? (hours / 8).toFixed(3) : hours.toFixed(2);
        cell.value = displayed;
    });
}

function convertToHours(value) {
    if (displayMode === 'days') {
        return parseFloat(value) * 8;
    }
    return parseFloat(value);
}

// Lors de la sauvegarde
function saveCell(cell) {
    const inputValue = cell.value;
    const hours = convertToHours(inputValue);

    // Appel AJAX existant avec hours
    // ...
}
```

---

#### 3. Vue Calendrier Mensuel ⭐ Priorité Haute
**Nouveau** : Route `/timesheet/calendar`

**À implémenter** :
- Nouvelle route dans `TimesheetController`
- Template avec FullCalendar
- Affichage des temps saisis comme événements
- Saisie rapide via modal au clic sur un jour

**Nouvelle route** :
```php
// Dans TimesheetController
#[Route('/calendar', name: 'timesheet_calendar', methods: ['GET'])]
public function calendar(Request $request, EntityManagerInterface $em): Response
{
    $month = $request->query->get('month', date('Y-m'));
    $startDate = new DateTime($month . '-01');
    $endDate = clone $startDate;
    $endDate->modify('last day of this month');

    $contributor = $em->getRepository(Contributor::class)->findByUser($this->getUser());
    if (!$contributor) {
        $this->addFlash('error', 'Contributeur non trouvé');
        return $this->redirectToRoute('home');
    }

    $timesheets = $em->getRepository(Timesheet::class)
        ->findByContributorAndDateRange($contributor, $startDate, $endDate);

    // Transformer en format FullCalendar
    $events = [];
    foreach ($timesheets as $ts) {
        $events[] = [
            'id' => $ts->getId(),
            'title' => sprintf(
                '%s - %.2fh',
                $ts->getProject()->getName(),
                $ts->getHours()
            ),
            'start' => $ts->getDate()->format('Y-m-d'),
            'allDay' => true,
            'backgroundColor' => '#3788d8',
            'extendedProps' => [
                'projectId' => $ts->getProject()->getId(),
                'taskId' => $ts->getTask()?->getId(),
                'hours' => $ts->getHours(),
                'notes' => $ts->getNotes(),
            ],
        ];
    }

    return $this->render('timesheet/calendar.html.twig', [
        'events' => $events,
        'month' => $month,
        'contributor' => $contributor,
    ]);
}
```

**Template FullCalendar** :
```twig
{# templates/timesheet/calendar.html.twig #}
{% extends 'base.html.twig' %}

{% block title %}Calendrier des temps{% endblock %}

{% block body %}
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="page-title-box">
                <h4 class="page-title">📅 Calendrier de saisie des temps</h4>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal saisie rapide -->
<div class="modal fade" id="timesheetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Saisir du temps</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="quick-timesheet-form">
                    <input type="hidden" id="modal-date" name="date">
                    <div class="mb-3">
                        <label for="modal-project" class="form-label">Projet</label>
                        <select id="modal-project" name="project_id" class="form-select" required>
                            <option value="">-- Sélectionner --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modal-task" class="form-label">Tâche</label>
                        <select id="modal-task" name="task_id" class="form-select">
                            <option value="">-- Aucune --</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="modal-hours" class="form-label">Heures</label>
                        <input type="number" id="modal-hours" name="hours" class="form-control"
                               step="0.25" min="1" max="24" required>
                    </div>
                    <div class="mb-3">
                        <label for="modal-notes" class="form-label">Notes</label>
                        <textarea id="modal-notes" name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="save-timesheet-btn">Enregistrer</button>
            </div>
        </div>
    </div>
</div>
{% endblock %}

{% block javascripts %}
    {{ parent() }}
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            const modal = new bootstrap.Modal(document.getElementById('timesheetModal'));

            const calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'fr',
                firstDay: 1,
                events: {{ events|json_encode|raw }},
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,dayGridWeek'
                },
                dateClick: function(info) {
                    // Ouvrir modal de saisie
                    document.getElementById('modal-date').value = info.dateStr;
                    modal.show();
                },
                eventClick: function(info) {
                    // Ouvrir modal d'édition
                    const props = info.event.extendedProps;
                    document.getElementById('modal-date').value = info.event.startStr;
                    document.getElementById('modal-hours').value = props.hours;
                    document.getElementById('modal-notes').value = props.notes || '';
                    // Pré-sélectionner projet/tâche...
                    modal.show();
                }
            });

            calendar.render();

            // Sauvegarde via AJAX
            document.getElementById('save-timesheet-btn').addEventListener('click', function() {
                const form = document.getElementById('quick-timesheet-form');
                const formData = new FormData(form);

                fetch('{{ path('timesheet_save') }}', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        modal.hide();
                        location.reload(); // Recharger le calendrier
                    } else {
                        alert('Erreur : ' + data.error);
                    }
                });
            });
        });
    </script>
{% endblock %}
```

---

#### 4. Copie de semaine / Duplication ⭐ Priorité Moyenne
**Nouveau** : Route `/timesheet/duplicate-week`

**À implémenter** :
- Bouton "Dupliquer la semaine" dans la grille hebdomadaire
- Modal de confirmation avec sélection semaine source/cible
- Duplication des temps (projets + tâches + heures, pas les notes)

**Nouvelle route** :
```php
// Dans TimesheetController
#[Route('/duplicate-week', name: 'timesheet_duplicate_week', methods: ['POST'])]
public function duplicateWeek(Request $request, EntityManagerInterface $em): JsonResponse
{
    $contributor = $em->getRepository(Contributor::class)->findByUser($this->getUser());
    if (!$contributor) {
        return new JsonResponse(['error' => 'Contributeur non trouvé'], 400);
    }

    $sourceWeek = $request->request->get('source_week'); // Format: 2025-W04
    $targetWeek = $request->request->get('target_week'); // Format: 2025-W05

    list($sourceYear, $sourceWeekNum) = explode('-W', $sourceWeek);
    list($targetYear, $targetWeekNum) = explode('-W', $targetWeek);

    $sourceStart = new DateTime();
    $sourceStart->setISODate((int)$sourceYear, (int)$sourceWeekNum, 1);
    $sourceEnd = clone $sourceStart;
    $sourceEnd->modify('+6 days');

    $targetStart = new DateTime();
    $targetStart->setISODate((int)$targetYear, (int)$targetWeekNum, 1);

    // Récupérer les temps de la semaine source
    $sourceTimesheets = $em->getRepository(Timesheet::class)
        ->findByContributorAndDateRange($contributor, $sourceStart, $sourceEnd);

    if (empty($sourceTimesheets)) {
        return new JsonResponse(['error' => 'Aucun temps à dupliquer pour cette semaine'], 400);
    }

    $duplicatedCount = 0;
    foreach ($sourceTimesheets as $source) {
        // Calculer le décalage de jours entre source et cible
        $dayOffset = $source->getDate()->diff($sourceStart)->days;
        $targetDate = clone $targetStart;
        $targetDate->modify("+{$dayOffset} days");

        // Vérifier si un temps existe déjà
        $existing = $em->getRepository(Timesheet::class)
            ->findExistingTimesheetWithTask($contributor, $source->getProject(), $targetDate, $source->getTask());

        if (!$existing) {
            $duplicate = new Timesheet();
            $duplicate->setContributor($contributor)
                ->setProject($source->getProject())
                ->setTask($source->getTask())
                ->setSubTask($source->getSubTask())
                ->setDate($targetDate)
                ->setHours($source->getHours());
            // Notes non dupliquées volontairement

            $em->persist($duplicate);
            $duplicatedCount++;
        }
    }

    $em->flush();

    return new JsonResponse([
        'success' => true,
        'message' => sprintf('%d entrée(s) dupliquée(s)', $duplicatedCount),
        'duplicated_count' => $duplicatedCount,
    ]);
}
```

**Ajout interface (bouton dans index.html.twig)** :
```twig
<button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#duplicateWeekModal">
    <i class="mdi mdi-content-copy"></i> Dupliquer la semaine
</button>

<!-- Modal duplication -->
<div class="modal fade" id="duplicateWeekModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dupliquer une semaine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Semaine source</label>
                    <input type="week" id="source-week" class="form-control" value="{{ currentWeek }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Semaine cible</label>
                    <input type="week" id="target-week" class="form-control">
                </div>
                <div class="alert alert-info">
                    <i class="mdi mdi-information"></i>
                    Les projets, tâches et heures seront copiés. Les notes ne seront pas dupliquées.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <button type="button" class="btn btn-primary" id="confirm-duplicate-btn">Dupliquer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('confirm-duplicate-btn').addEventListener('click', function() {
    const sourceWeek = document.getElementById('source-week').value;
    const targetWeek = document.getElementById('target-week').value;

    if (!sourceWeek || !targetWeek) {
        alert('Veuillez sélectionner les deux semaines');
        return;
    }

    if (sourceWeek === targetWeek) {
        alert('Les semaines source et cible doivent être différentes');
        return;
    }

    const formData = new FormData();
    formData.append('source_week', sourceWeek);
    formData.append('target_week', targetWeek);

    fetch('{{ path('timesheet_duplicate_week') }}', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert('Erreur : ' + data.error);
        }
    });
});
</script>
```

---

#### 5. Export Excel ⭐ Priorité Basse
**Nouveau** : Route `/timesheet/export`

**À implémenter** :
- Export des temps personnels au format Excel
- Utilisation de PhpSpreadsheet (déjà installé ?)
- Filtres : période, projet

**Vérifier installation** :
```bash
composer show | grep phpspreadsheet
# Si absent :
composer require phpoffice/phpspreadsheet
```

**Nouvelle route** :
```php
// Dans TimesheetController
#[Route('/export', name: 'timesheet_export', methods: ['GET'])]
public function export(Request $request, EntityManagerInterface $em): Response
{
    $contributor = $em->getRepository(Contributor::class)->findByUser($this->getUser());
    if (!$contributor) {
        throw $this->createNotFoundException('Contributeur non trouvé');
    }

    $startDate = new DateTime($request->query->get('start', date('Y-m-01')));
    $endDate = new DateTime($request->query->get('end', date('Y-m-t')));

    $timesheets = $em->getRepository(Timesheet::class)
        ->findByContributorAndDateRange($contributor, $startDate, $endDate);

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // En-têtes
    $sheet->setCellValue('A1', 'Date');
    $sheet->setCellValue('B1', 'Projet');
    $sheet->setCellValue('C1', 'Client');
    $sheet->setCellValue('D1', 'Tâche');
    $sheet->setCellValue('E1', 'Heures');
    $sheet->setCellValue('F1', 'Notes');

    // Style en-têtes
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);

    // Données
    $row = 2;
    foreach ($timesheets as $ts) {
        $sheet->setCellValue('A' . $row, $ts->getDate()->format('d/m/Y'));
        $sheet->setCellValue('B' . $row, $ts->getProject()->getName());
        $sheet->setCellValue('C' . $row, $ts->getProject()->getClient()?->getName() ?? 'N/A');
        $sheet->setCellValue('D' . $row, $ts->getTask()?->getName() ?? 'N/A');
        $sheet->setCellValue('E' . $row, (float) $ts->getHours());
        $sheet->setCellValue('F' . $row, $ts->getNotes() ?? '');
        $row++;
    }

    // Total
    $sheet->setCellValue('D' . $row, 'TOTAL');
    $sheet->setCellValue('E' . $row, '=SUM(E2:E' . ($row - 1) . ')');
    $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);

    // Auto-size colonnes
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }

    // Génération fichier
    $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
    $filename = sprintf(
        'temps_%s_%s_%s.xlsx',
        $contributor->getLastName(),
        $startDate->format('Ymd'),
        $endDate->format('Ymd')
    );

    $response = new StreamedResponse(function() use ($writer) {
        $writer->save('php://output');
    });

    $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

    return $response;
}
```

---

## 📝 Tests à implémenter

### Tests Fonctionnels

**Fichier** : `tests/Functional/TimesheetControllerTest.php`

```php
<?php

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TimesheetControllerTest extends WebTestCase
{
    public function testValidationMax24Hours(): void
    {
        $client = static::createClient();
        // TODO: Authentifier un utilisateur
        // TODO: Créer 20h de temps pour aujourd'hui
        // TODO: Tenter d'ajouter 5h supplémentaires
        // TODO: Vérifier que l'erreur est retournée

        $this->markTestIncomplete('À implémenter');
    }

    public function testValidationMinimum1Hour(): void
    {
        $this->markTestIncomplete('À implémenter');
    }

    public function testDuplicateWeek(): void
    {
        $this->markTestIncomplete('À implémenter');
    }

    public function testExportExcel(): void
    {
        $this->markTestIncomplete('À implémenter');
    }
}
```

### Tests E2E

**Fichier** : `tests/E2E/TimesheetE2ETest.php`

```php
<?php

namespace App\Tests\E2E;

use Symfony\Component\Panther\PantherTestCase;

class TimesheetE2ETest extends PantherTestCase
{
    public function testCompleteTimesheetFlow(): void
    {
        $client = static::createPantherClient();

        // TODO: Login
        // TODO: Naviguer vers /timesheet
        // TODO: Saisir des heures sur plusieurs jours
        // TODO: Vérifier auto-save
        // TODO: Changer de semaine
        // TODO: Dupliquer la semaine
        // TODO: Vérifier que les temps sont bien dupliqués

        $this->markTestIncomplete('À implémenter');
    }
}
```

---

## 📋 Checklist Sprint 1

### Semaine 1

- [ ] **Jour 1-2 : Validation des données**
  - [ ] Créer méthode `getTotalHoursForContributorAndDate()` dans TimesheetRepository
  - [ ] Ajouter validation 24h/jour dans `TimesheetController::save()`
  - [ ] Ajouter validation minimum 1h
  - [ ] Tester manuellement
  - [ ] Écrire tests fonctionnels

- [ ] **Jour 3 : Conversion Heures ↔ Jours**
  - [ ] Créer fichier JavaScript `assets/js/timesheet.js`
  - [ ] Implémenter toggle et conversion
  - [ ] Ajouter bouton dans template index.html.twig
  - [ ] Tester conversion dans les deux sens
  - [ ] Webpack build

- [ ] **Jour 4-5 : Vue Calendrier**
  - [ ] Installer FullCalendar (npm ou CDN)
  - [ ] Créer route `/timesheet/calendar`
  - [ ] Créer template avec FullCalendar
  - [ ] Implémenter modal de saisie rapide
  - [ ] Tester affichage et saisie

### Semaine 2

- [ ] **Jour 6-7 : Copie de semaine**
  - [ ] Créer route `/timesheet/duplicate-week`
  - [ ] Ajouter modal dans index.html.twig
  - [ ] Implémenter logique de duplication
  - [ ] Gérer cas d'erreur (semaine vide, conflit dates)
  - [ ] Tests fonctionnels

- [ ] **Jour 8 : Export Excel**
  - [ ] Vérifier/installer PhpSpreadsheet
  - [ ] Créer route `/timesheet/export`
  - [ ] Générer fichier Excel avec totaux
  - [ ] Ajouter bouton export dans interface
  - [ ] Tester téléchargement

- [ ] **Jour 9 : Tests**
  - [ ] Écrire tests fonctionnels pour toutes les routes
  - [ ] Écrire test E2E parcours complet
  - [ ] Corriger bugs trouvés

- [ ] **Jour 10 : Finalisation**
  - [ ] Revue de code
  - [ ] Documentation mise à jour
  - [ ] Démo pour validation utilisateur
  - [ ] Préparer Sprint 2

---

## 🎯 Définition of Done (DoD)

### Fonctionnel
- ✅ Toutes les fonctionnalités listées sont implémentées
- ✅ Validation utilisateur en démo
- ✅ Aucun bug bloquant

### Qualité Code
- ✅ PSR-12 respecté (php-cs-fixer)
- ✅ PHPStan level 3 sans erreur
- ✅ Tests fonctionnels au vert
- ✅ Test E2E au vert

### Documentation
- ✅ Code commenté (méthodes publiques)
- ✅ Ce document Sprint 1 à jour
- ✅ CHANGELOG.md mis à jour

---

## 🚀 Prochaines Étapes (Sprint 2)

Une fois le Sprint 1 terminé, nous passerons au Sprint 2 qui inclura :
- Workflow de validation hiérarchique (brouillon → validé → approuvé)
- Entité `TimesheetValidation` pour historique
- Commentaires de validation
- Notifications aux managers
- Interface de validation pour chefs de projet
- vérification de l'affichage de `/timewheet/all` qui ne semble pas valide

---

**Document créé le** : 23 novembre 2025
**Statut** : Prêt à démarrer
**Responsable** : [Votre nom]
**Estimation** : 10 jours (2 semaines)
