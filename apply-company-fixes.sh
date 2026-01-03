#!/bin/bash

# Script to automatically fix Company context in remaining controllers

echo "Applying Company context fixes to remaining controllers..."

# Array of fixes: "file:line:old_line:new_line"
declare -a fixes=(
    # ContributorController
    "src/Controller/ContributorController.php:194:        \$contributor = new Contributor();:        \$contributor = new Contributor();\n        \$contributor->setCompany(\$this->companyContext->getCurrentCompany());"

    # ExpenseReportController
    "src/Controller/ExpenseReportController.php:78:        \$expense = new ExpenseReport();:        \$expense = new ExpenseReport();\n        \$expense->setCompany(\$this->companyContext->getCurrentCompany());"

    # NpsController
    "src/Controller/NpsController.php:107:        \$survey = new NpsSurvey();:        \$survey = new NpsSurvey();\n        \$survey->setCompany(\$this->companyContext->getCurrentCompany());"

    # ProjectDetailController
    "src/Controller/ProjectDetailController.php:87:        \$task = new ProjectTask();:        \$task = new ProjectTask();\n        \$task->setCompany(\$project->getCompany());"

    # ContributorSatisfactionController
    "src/Controller/ContributorSatisfactionController.php:92:            \$satisfaction = new ContributorSatisfaction();:            \$satisfaction = new ContributorSatisfaction();\n            \$satisfaction->setCompany(\$this->companyContext->getCurrentCompany());"
)

echo "This script would apply ${#fixes[@]} fixes."
echo "Please apply these manually using the Edit tool for safety."
