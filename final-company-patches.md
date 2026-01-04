# Final Company Context Patches - 9 Controllers Restants

## Controllers avec constructors existants (5)

### 1. ProjectDetailController (ligne 89)
```php
// AJOUTER au constructor existant:
private readonly CompanyContext $companyContext

// REMPLACER ligne 89:
$task = new ProjectTask();
// PAR:
$task = new ProjectTask();
$task->setCompany($project->getCompany());
```

### 2. ContributorSatisfactionController (ligne 94)
```php
// AJOUTER au constructor existant:
private readonly CompanyContext $companyContext

// REMPLACER ligne 94:
$satisfaction = new ContributorSatisfaction();
// PAR:
$satisfaction = new ContributorSatisfaction();
$satisfaction->setCompany($this->companyContext->getCurrentCompany());
```

### 3. BadgeController (ligne 43)
```php
// AJOUTER au constructor existant:
private readonly CompanyContext $companyContext

// REMPLACER ligne 43:
$badge = new Badge();
// PAR:
$badge = new Badge();
$badge->setCompany($this->companyContext->getCurrentCompany());
```

### 4. ContributorSkillController (ligne 54)
```php
// AJOUTER au constructor existant:
private readonly CompanyContext $companyContext

// REMPLACER ligne 54:
$skill = new ContributorSkill();
// PAR:
$skill = new ContributorSkill();
$skill->setCompany($this->companyContext->getCurrentCompany());
```

### 5. LeadMagnetController (ligne 54)
```php
// AJOUTER au constructor existant:
private readonly CompanyContext $companyContext

// REMPLACER ligne 54:
$lead = new LeadCapture();
// PAR:
$lead = new LeadCapture();
$lead->setCompany($this->companyContext->getCurrentCompany());
```

## Controllers SANS constructors (4) - Créer nouveau constructor

### 6. AdminUserController (ligne 126)
```php
// AJOUTER après class declaration:
public function __construct(
    private readonly CompanyContext $companyContext
) {
}

// REMPLACER ligne 126:
$contributor = new Contributor();
// PAR:
$contributor = new Contributor();
$contributor->setCompany($this->companyContext->getCurrentCompany());
```

### 7. SubscriptionController (ligne 144)
```php
// AJOUTER après class declaration:
public function __construct(
    private readonly CompanyContext $companyContext
) {
}

// REMPLACER ligne 144:
$subscription = new SaasSubscription();
// PAR:
$subscription = new SaasSubscription();
$subscription->setCompany($this->companyContext->getCurrentCompany());
```

### 8. ProjectTechnologyController (ligne 23)
```php
// AJOUTER après class declaration:
public function __construct(
    private readonly CompanyContext $companyContext
) {
}

// REMPLACER ligne 23:
$pt = new ProjectTechnology();
// PAR:
$pt = new ProjectTechnology();
$pt->setCompany($project->getCompany());
```

### 9. GdprController (ligne 42)
```php
// AJOUTER après class declaration:
public function __construct(
    private readonly CompanyContext $companyContext
) {
}

// REMPLACER ligne 42:
$consent = new CookieConsent();
// PAR:
$consent = new CookieConsent();
$consent->setCompany($this->companyContext->getCurrentCompany());
```
