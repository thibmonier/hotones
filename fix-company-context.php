#!/usr/bin/env php
<?php

/**
 * Script to fix Company context assignment in all controllers.
 *
 * This script:
 * 1. Injects CompanyContext into controller constructors
 * 2. Adds ->setCompany() calls after entity creation
 * 3. For child entities, inherits Company from parent
 */

declare(strict_types=1);

$fixes = [
    // [controllerFile, entityClass, method, line, inheritFrom (null = use CompanyContext)]
    ['src/Controller/TimesheetController.php', 'Timesheet', 'save', 170, null],
    ['src/Controller/TimesheetController.php', 'Timesheet', 'duplicateWeek', 402, null],
    ['src/Controller/TimesheetController.php', 'RunningTimer', 'startTimer', 477, null],
    ['src/Controller/TimesheetController.php', 'Timesheet', 'finalizeTimer', 625, null],
    ['src/Controller/OrderController.php', 'Order', 'new', 100, null],
    ['src/Controller/OrderController.php', 'OrderSection', 'addSection', 236, 'order'],
    ['src/Controller/OrderController.php', 'OrderLine', 'addLine', 333, 'order'],
    ['src/Controller/OrderController.php', 'Order', 'duplicate', 456, null],
    ['src/Controller/OrderController.php', 'OrderSection', 'duplicate', 468, 'order'],
    ['src/Controller/OrderController.php', 'OrderLine', 'duplicate', 477, 'section->order'],
    ['src/Controller/ProjectTaskController.php', 'ProjectTask', 'new', 58, null],
    ['src/Controller/ProjectTaskController.php', 'ProjectSubTask', 'newSubTask', 151, 'task'],
    ['src/Controller/ProjectDetailController.php', 'ProjectTask', 'newTask', 87, 'project'],
    ['src/Controller/InvoiceController.php', 'Invoice', 'new', 179, null],
    ['src/Controller/NpsController.php', 'NpsSurvey', 'new', 107, null],
    ['src/Controller/ContributorController.php', 'Contributor', 'new', 194, null],
    ['src/Controller/EmploymentPeriodController.php', 'EmploymentPeriod', 'new', 178, null],
    ['src/Controller/VacationRequestController.php', 'Vacation', 'new', 74, null],
    ['src/Controller/ExpenseReportController.php', 'ExpenseReport', 'new', 78, null],
    ['src/Controller/PlanningController.php', 'Planning', 'create', 338, null],
    ['src/Controller/PlanningController.php', 'Planning', 'split', 499, null],
    ['src/Controller/ContributorSatisfactionController.php', 'ContributorSatisfaction', 'submit', 92, null],
    ['src/Controller/GdprController.php', 'CookieConsent', 'saveCookieConsent', 42, null],
    ['src/Controller/LeadMagnetController.php', 'LeadCapture', 'guideKpis', 54, null],
    ['src/Controller/SubscriptionController.php', 'SaasSubscription', 'new', 144, null],
    ['src/Controller/BadgeController.php', 'Badge', 'new', 43, null],
    ['src/Controller/ContributorSkillController.php', 'ContributorSkill', 'new', 54, null],
    ['src/Controller/AdminUserController.php', 'Contributor', 'new', 126, null],
    ['src/Controller/ProjectTechnologyController.php', 'ProjectTechnology', 'manage', 23, 'project'],
];

$controllersNeedingInjection = [];
foreach ($fixes as $fix) {
    $controllersNeedingInjection[$fix[0]] = true;
}

echo "Controllers requiring Company context fixes:\n";
echo "============================================\n\n";

foreach (array_keys($controllersNeedingInjection) as $controller) {
    echo "- $controller\n";
}

echo "\nTotal: " . count($controllersNeedingInjection) . " controllers\n";
echo "\nThis is a planning script. Manual fixes should be applied using:\n";
echo "1. Inject CompanyContext via constructor\n";
echo "2. Add ->setCompany() calls after entity creation\n";
echo "3. For child entities, use parent->getCompany()\n\n";

// Print detailed fix instructions
echo "Detailed fix instructions:\n";
echo "=========================\n\n";

foreach ($fixes as $fix) {
    [$controller, $entity, $method, $line, $inheritFrom] = $fix;

    echo "File: $controller\n";
    echo "Method: $method (line ~$line)\n";
    echo "Entity: $entity\n";

    if ($inheritFrom === null) {
        echo "Fix: \$entity->setCompany(\$this->companyContext->getCurrentCompany());\n";
    } else {
        echo "Fix: \$entity->setCompany(\$$inheritFrom->getCompany());\n";
    }

    echo "\n";
}

echo "Note: ClientController and ProjectController have already been fixed.\n";
