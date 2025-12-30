# 🏢 Plan de Transformation SAAS Multi-Tenant HotOnes

> Plan détaillé pour transformer HotOnes en solution SAAS avec isolation multi-société et Business Units
>
> **Date de création** : 17 décembre 2024
> **Priorité** : 🟡 Stratégique (2026-2027)
> **Estimation totale** : 45-55 jours

---

## 📋 Table des matières

1. [Vision & Objectifs](#vision--objectifs)
2. [Architecture Cible](#architecture-cible)
3. [Plan de Migration](#plan-de-migration)
4. [Risques & Mitigation](#risques--mitigation)
5. [Estimation Détaillée](#estimation-détaillée)

---

## Vision & Objectifs

### Contexte Business

HotOnes est actuellement une **application single-tenant** : toutes les données appartiennent à une seule organisation. Pour devenir une solution SAAS commercialisable, il faut supporter :

1. **Multi-tenant (Société/Organization)** : Chaque client a son propre espace isolé
2. **Business Units (BU)** : Au sein d'une société, séparation en équipes/départements
3. **Isolation des données** : Garantie que les données d'un tenant ne sont jamais visibles par un autre
4. **Modèle de facturation** : Par tenant (nombre d'utilisateurs, projets, stockage)

### Bénéfices Attendus

| Bénéfice | Impact |
|----------|--------|
| **Scalabilité commerciale** | 1 déploiement → N clients |
| **Réduction des coûts** | Infra mutualisée vs N serveurs |
| **Accélération onboarding** | Client provisionné en < 5 min |
| **Revenus récurrents** | MRR/ARR prévisible |
| **Analytics cross-tenant** | Benchmarks anonymisés entre clients |

### Cas d'Usage

#### Scenario 1 : Agence web classique (mono-tenant actuel)
- 1 société = 1 tenant
- Pas de BU, tous les collaborateurs voient tout
- Migration transparente depuis la version actuelle

#### Scenario 2 : Groupe d'agences (multi-tenant simple)
- Groupe avec 3 filiales indépendantes
- Chaque filiale = 1 tenant distinct
- Pas de partage de données entre filiales
- Facturation par filiale

#### Scenario 3 : Grande ESN (multi-tenant + BU)
- 1 société avec 5 Business Units (Web, Mobile, Data, DevOps, Conseil)
- Chaque BU a son propre dashboard, budget, objectifs
- Direction voit consolidation globale
- Contributeur ne voit que sa BU

---

## Architecture Cible

### Modèle de Données

#### Niveau 1 : Tenant Root (Company)

```php
/**
 * Company = Tenant racine (compte client SAAS)
 * 1 Company = 1 contrat commercial, 1 facturation
 */
class Company
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name; // "Agence WebFlow", "ESN TechCorp"

    #[ORM\Column(length: 100, unique: true)]
    private string $slug; // "agence-webflow", "esn-techcorp" (subdomain identifier)

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private User $owner; // Owner du compte (admin principal)

    #[ORM\Column(length: 50)]
    private string $status = 'active'; // active, suspended, cancelled

    #[ORM\Column(length: 50)]
    private string $subscriptionTier = 'professional'; // starter, professional, enterprise

    // Limites de l'abonnement
    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxUsers = null; // null = unlimited (enterprise)

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxProjects = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxStorageMb = null; // espace fichiers (10 000 MB = 10 GB)

    // Facturation
    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $billingStartDate;

    #[ORM\Column(type: 'integer')]
    private int $billingDayOfMonth = 1; // jour de facturation (1-28)

    // Paramètres globaux
    #[ORM\Column(type: 'json')]
    private array $settings = []; // timezone, currency, language, logo, etc.

    #[ORM\Column(type: 'json')]
    private array $features = []; // modules activés : ['invoicing', 'planning', 'analytics']

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $suspendedAt = null;

    // Relations
    #[ORM\OneToMany(mappedBy: 'company', targetEntity: BusinessUnit::class, cascade: ['remove'])]
    private Collection $businessUnits;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: User::class, cascade: ['remove'])]
    private Collection $users;

    #[ORM\OneToMany(mappedBy: 'company', targetEntity: Project::class, cascade: ['remove'])]
    private Collection $projects;

    // ... tous les autres OneToMany (clients, orders, timesheets, etc.)
}
```

**Points clés :**
- `slug` : Identifiant unique pour subdomain (`{slug}.hotones.io`)
- `status` : Permet suspension/annulation du compte (impayé, résiliation)
- `subscriptionTier` : Définit les limites et fonctionnalités accessibles
- **Cascade DELETE** : Suppression d'une Company supprime TOUTES ses données (RGPD right to erasure)

---

#### Niveau 2 : Business Unit (Département/Équipe)

```php
/**
 * BusinessUnit = Subdivision au sein d'une Company
 * Permet cloisonnement interne : chaque BU a son propre P&L, objectifs, dashboard
 */
class BusinessUnit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'businessUnits')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Company $company;

    #[ORM\ManyToOne(targetEntity: self::class, inversedBy: 'children')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'CASCADE')]
    private ?self $parent = null; // Hiérarchie de BU (ex: BU "Web" parent de "Web Mobile" et "Web Backend")

    #[ORM\Column(length: 255)]
    private string $name; // "BU Web", "BU Data", "BU Mobile"

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $manager = null; // Responsable de la BU

    // Objectifs annuels
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true)]
    private ?string $annualRevenueTarget = null; // Objectif CA annuel

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $annualMarginTarget = null; // Objectif marge (%)

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $headcountTarget = null; // Effectif cible

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $costCenter = null; // Centre de coûts (comptabilité analytique)

    #[ORM\Column(type: 'boolean')]
    private bool $active = true;

    // Relations
    #[ORM\OneToMany(mappedBy: 'businessUnit', targetEntity: Contributor::class)]
    private Collection $contributors; // Membres de la BU

    #[ORM\OneToMany(mappedBy: 'businessUnit', targetEntity: Project::class)]
    private Collection $projects;

    #[ORM\OneToMany(mappedBy: 'businessUnit', targetEntity: Client::class)]
    private Collection $clients; // Optionnel : clients gérés par la BU

    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: self::class)]
    private Collection $children; // BU enfants

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;
}
```

**Points clés :**
- **Optionnel** : Une Company peut fonctionner sans BU (toutes les données à la racine)
- **Hiérarchique** : Arbre de BU possible (parent/children)
- **Objectifs propres** : Chaque BU a ses propres KPIs à atteindre
- **Manager** : Responsable de BU (permissions étendues sur sa BU)

---

#### Niveau 3 : Modification des Entités Existantes

**Toutes les 45 entités existantes** doivent être modifiées pour ajouter :

```php
/**
 * Exemple : Project (avant/après)
 */
class Project
{
    // AVANT : pas de référence à Company/BU
    private ?int $id = null;
    private string $name;
    // ...

    // APRÈS : ajout de Company + BU
    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Company $company; // ⬅️ OBLIGATOIRE (NOT NULL)

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class, inversedBy: 'projects')]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?BusinessUnit $businessUnit = null; // ⬅️ OPTIONNEL (peut être NULL si pas de BU)

    // ...
}
```

**Liste des entités à modifier** (45 entités) :

| Catégorie | Entités |
|-----------|---------|
| **Core** | User, Contributor, EmploymentPeriod, Profile |
| **Projects** | Project, Client, ClientContact |
| **Orders** | Order, OrderSection, OrderLine, OrderPaymentSchedule |
| **Time** | Timesheet, ProjectTask, ProjectSubTask, Planning |
| **Config** | Technology, ServiceCategory, CompanySettings, Skill, Badge, Achievement |
| **Analytics** | FactProjectMetrics, FactStaffingMetrics, DimTime, DimProjectType, DimContributor, DimProfile |
| **Notifications** | Notification, OnboardingTask |
| **RH** | Vacation, ExpenseReport, ContributorSkill |
| **(Futurs)** | Invoice, InvoiceLine, Contract, Purchase, Supplier, Candidate, PrivacyRequest, etc. |

**Contraintes uniques à ajuster** :

```php
// AVANT : UNIQUE(email)
#[ORM\Column(type: 'string', unique: true)]
private string $email;

// APRÈS : UNIQUE(email, company_id) - 1 email par company
#[ORM\UniqueConstraint(name: 'email_company_unique', columns: ['email', 'company_id'])]
#[ORM\Column(type: 'string')]
private string $email;
```

**Autres champs concernés** :
- `User.email` → unique par company
- `Order.orderNumber` → unique par company (D202501001, D202501002, ...)
- `Technology.name`, `ServiceCategory.name` → unique par company (ou partagé globalement ?)
- `Client.name` → pas unique (2 clients peuvent avoir le même nom dans 2 companies)

---

### Authentification & Autorisation

#### Relation User ↔ Company

**Option 1 : User appartient à 1 seule Company (simple)**

```php
class User
{
    #[ORM\ManyToOne(targetEntity: Company::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Company $company; // 1 User = 1 Company

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER']; // Rôles globaux dans la company
}
```

**Avantages :**
- Simple à implémenter
- Pas de gestion de contexte multi-company
- User connecté → accès immédiat à sa company

**Inconvénients :**
- Impossible de gérer plusieurs companies avec le même user (ex: consultant externe)

---

**Option 2 : User peut appartenir à plusieurs Companies (avancé)**

```php
/**
 * UserCompanyRole = Rôle d'un User dans une Company donnée
 * Permet à 1 User de faire partie de plusieurs Companies avec des rôles différents
 */
class UserCompanyRole
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'companyRoles')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Company $company;

    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER']; // Rôles du user dans cette company

    #[ORM\ManyToOne(targetEntity: BusinessUnit::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?BusinessUnit $businessUnit = null; // Optionnel : accès limité à une BU

    #[ORM\Column(type: 'json')]
    private array $permissions = []; // Fine-grained permissions (ABAC)

    #[ORM\Column(type: 'boolean')]
    private bool $isActive = true;
}

class User
{
    #[ORM\ManyToOne(targetEntity: Company::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Company $primaryCompany = null; // Company par défaut à la connexion

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: UserCompanyRole::class, cascade: ['remove'])]
    private Collection $companyRoles;

    public function getCompanies(): array
    {
        return array_map(fn($ucr) => $ucr->getCompany(), $this->companyRoles->toArray());
    }
}
```

**Avantages :**
- Flexibilité totale (consultant peut intervenir chez N clients)
- Permissions granulaires par company

**Inconvénients :**
- Complexité accrue (gestion du contexte, switch entre companies)
- Nécessite un sélecteur de company dans l'UI

**Recommandation : Option 1 pour MVP SAAS, Option 2 si besoin futur confirmé**

---

#### Context Management (CompanyContext Service)

```php
/**
 * CompanyContext = Service pour récupérer la Company courante
 * Injecté dans tous les controllers/services qui accèdent aux données
 */
class CompanyContext
{
    public function __construct(
        private Security $security,
        private RequestStack $requestStack
    ) {}

    /**
     * Retourne la Company courante basée sur :
     * - Option 1 : User.company
     * - Option 2 : Session "current_company_id" (si multi-company)
     * - Fallback : Détection par subdomain ({slug}.hotones.io)
     */
    public function getCurrentCompany(): Company
    {
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new AccessDeniedException('User not authenticated');
        }

        // Option 1 : Simple (1 user = 1 company)
        return $user->getCompany();

        // Option 2 : Multi-company (récupère depuis session + valide que user a accès)
        $companyId = $this->requestStack->getSession()->get('current_company_id');
        $company = $this->findCompanyById($companyId);

        if (!$user->hasAccessToCompany($company)) {
            throw new AccessDeniedException('User does not have access to this company');
        }

        return $company;
    }

    /**
     * Change de company (Option 2 multi-company uniquement)
     */
    public function switchCompany(Company $company): void
    {
        $user = $this->security->getUser();

        if (!$user->hasAccessToCompany($company)) {
            throw new AccessDeniedException();
        }

        $this->requestStack->getSession()->set('current_company_id', $company->getId());
    }
}
```

**Utilisation dans les controllers :**

```php
class ProjectController extends AbstractController
{
    #[Route('/projects', name: 'project_index')]
    public function index(
        CompanyContext $companyContext,
        ProjectRepository $projectRepo
    ): Response {
        $company = $companyContext->getCurrentCompany();
        $projects = $projectRepo->findByCompany($company);

        return $this->render('project/index.html.twig', [
            'projects' => $projects,
        ]);
    }
}
```

---

#### Voters pour Isolation des Données

```php
/**
 * CompanyVoter = S'assure que le User accède uniquement aux données de sa Company
 */
class CompanyVoter extends Voter
{
    const VIEW = 'view';
    const EDIT = 'edit';
    const DELETE = 'delete';

    protected function supports(string $attribute, $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT, self::DELETE])
            && $subject instanceof CompanyOwnedInterface; // Interface pour entités multi-tenant
    }

    protected function voteOnAttribute(string $attribute, $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var CompanyOwnedInterface $subject */
        $subjectCompany = $subject->getCompany();
        $userCompany = $user->getCompany(); // Ou via UserCompanyRole

        // Règle de base : user ne peut accéder qu'aux données de sa company
        if ($subjectCompany !== $userCompany) {
            return false;
        }

        // Règles supplémentaires selon l'action
        return match($attribute) {
            self::VIEW => true, // Tous les users de la company peuvent voir
            self::EDIT => $this->canEdit($user, $subject),
            self::DELETE => $this->canDelete($user, $subject),
        };
    }

    private function canEdit(User $user, $subject): bool
    {
        // Ex: Seuls ROLE_MANAGER+ peuvent modifier les projets
        return in_array('ROLE_MANAGER', $user->getRoles());
    }

    private function canDelete(User $user, $subject): bool
    {
        return in_array('ROLE_ADMIN', $user->getRoles());
    }
}

/**
 * Interface pour marquer les entités multi-tenant
 */
interface CompanyOwnedInterface
{
    public function getCompany(): Company;
}
```

**Utilisation dans les controllers :**

```php
#[Route('/projects/{id}', name: 'project_show')]
public function show(Project $project, CompanyContext $ctx): Response
{
    // Doctrine charge le projet par ID, mais ne vérifie pas la company
    // Le Voter s'en charge automatiquement
    $this->denyAccessUnlessGranted('view', $project);

    return $this->render('project/show.html.twig', [
        'project' => $project,
    ]);
}
```

---

#### API REST Multi-Tenant

**Option A : Prefix par Company dans l'URL**

```http
GET /api/v1/companies/{companyId}/projects
GET /api/v1/companies/{companyId}/timesheets
POST /api/v1/companies/{companyId}/projects
```

**Avantages :**
- Explicit (company dans l'URL)
- RESTful

**Inconvénients :**
- Verbeux
- Nécessite de passer companyId à chaque requête

---

**Option B : Company dans JWT (recommandé)**

```json
{
  "sub": "user_id",
  "email": "john@example.com",
  "company_id": "42",
  "roles": ["ROLE_MANAGER"],
  "exp": 1672531200
}
```

```http
GET /api/v1/projects
Authorization: Bearer <JWT avec company_id>
```

**Avantages :**
- API simple (pas de répétition de company_id)
- Company automatiquement inférée depuis JWT

**Inconvénients :**
- Moins explicit (company "cachée" dans le token)

**Recommandation : Option B avec validation stricte côté backend**

---

### Query Scoping (Isolation des Requêtes)

#### Option 1 : Doctrine Filter (Automatique)

```php
/**
 * TenantFilter = Ajoute automatiquement WHERE company_id = X à TOUTES les requêtes
 */
class TenantFilter extends SQLFilter
{
    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias): string
    {
        // Vérifie si l'entité a un champ company_id
        if (!$targetEntity->hasField('company') && !$targetEntity->hasAssociation('company')) {
            return '';
        }

        return sprintf('%s.company_id = %s', $targetTableAlias, $this->getParameter('companyId'));
    }
}

// Configuration (config/packages/doctrine.yaml)
doctrine:
    orm:
        filters:
            tenant_filter:
                class: App\Doctrine\TenantFilter
                enabled: true

// Activation dans un Listener
class TenantFilterListener
{
    public function onKernelRequest(RequestEvent $event, CompanyContext $ctx, EntityManagerInterface $em): void
    {
        $company = $ctx->getCurrentCompany();

        $filter = $em->getFilters()->enable('tenant_filter');
        $filter->setParameter('companyId', $company->getId());
    }
}
```

**Avantages :**
- Automatique (impossible d'oublier le filtre)
- Fonctionne sur toutes les entités

**Inconvénients :**
- Magie noire (peut cacher des bugs)
- Performance (filtre ajouté partout, même si pas nécessaire)
- Debugging complexe

---

#### Option 2 : Repository Scoping (Explicite, recommandé)

```php
/**
 * CompanyAwareRepository = Base class pour tous les repositories
 */
abstract class CompanyAwareRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        string $entityClass,
        private CompanyContext $companyContext
    ) {
        parent::__construct($registry, $entityClass);
    }

    /**
     * Crée un QueryBuilder avec filtre company automatique
     */
    protected function createCompanyQueryBuilder(string $alias): QueryBuilder
    {
        $company = $this->companyContext->getCurrentCompany();

        return $this->createQueryBuilder($alias)
            ->where(sprintf('%s.company = :company', $alias))
            ->setParameter('company', $company);
    }

    /**
     * findAll() scopé par company
     */
    public function findAllForCurrentCompany(): array
    {
        return $this->createCompanyQueryBuilder('e')
            ->getQuery()
            ->getResult();
    }
}

/**
 * Exemple : ProjectRepository
 */
class ProjectRepository extends CompanyAwareRepository implements CompanyOwnedRepositoryInterface
{
    public function __construct(ManagerRegistry $registry, CompanyContext $companyContext)
    {
        parent::__construct($registry, Project::class, $companyContext);
    }

    public function findActiveProjects(): array
    {
        return $this->createCompanyQueryBuilder('p')
            ->andWhere('p.status = :status')
            ->setParameter('status', 'active')
            ->orderBy('p.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findByClient(Client $client): array
    {
        // Le client est déjà scopé par company (vérifié par Voter)
        // Mais on ajoute quand même le filtre pour sécurité defense-in-depth
        return $this->createCompanyQueryBuilder('p')
            ->andWhere('p.client = :client')
            ->setParameter('client', $client)
            ->getQuery()
            ->getResult();
    }
}
```

**Avantages :**
- Explicit (on voit le filtre dans le code)
- Flexible (on peut désactiver le filtre si besoin)
- Debugging facile

**Inconvénients :**
- Nécessite de modifier tous les repositories (36 classes)
- Risque d'oublier le filtre (mitigé par code review + tests)

**Recommandation : Option 2 (explicit > magic)**

---

## Plan de Migration

### Phase 1 : Préparation & Design (Semaine 1-2, 5-7 jours)

#### Objectif
Concevoir l'architecture cible et préparer la migration sans toucher au code de production.

#### Tâches

1. **Audit complet des entités** (1j)
   - Lister les 45 entités à modifier
   - Identifier les contraintes UNIQUE à ajuster
   - Détecter les relations Many:Many qui nécessitent company_id dans la table de jointure

2. **Design des entités Company et BusinessUnit** (1j)
   - Créer les classes PHP
   - Définir les champs et relations
   - Valider avec les stakeholders

3. **Choisir l'approche authentication** (0.5j)
   - Option 1 (1 User = 1 Company) vs Option 2 (User Many:Many Company)
   - Décision : **Option 1 pour MVP**, migration vers Option 2 si besoin futur

4. **Choisir l'approche query scoping** (0.5j)
   - Doctrine Filter vs Repository Scoping
   - Décision : **Repository Scoping (explicit)**

5. **Design de l'API multi-tenant** (0.5j)
   - URL prefix vs JWT company_id
   - Décision : **JWT avec company_id claim**

6. **Plan de migration des données existantes** (1j)
   - Script SQL pour créer "Default Company" et migrer les données
   - Tests sur copie de la base de production

7. **Créer le plan de rollback** (0.5j)
   - Comment revenir en arrière si problème en prod

8. **Écrire les specs techniques détaillées** (1j)
   - Documentation pour les développeurs
   - Checklist de migration par entité
   - Exemples de code avant/après

---

### Phase 2 : Database & Models (Semaine 3-5, 15-18 jours)

#### Objectif
Ajouter Company et BusinessUnit, modifier toutes les entités existantes, créer les migrations.

#### Tâches

1. **Créer les entités Company et BusinessUnit** (2j)
   ```bash
   php bin/console make:entity Company
   php bin/console make:entity BusinessUnit
   php bin/console make:entity UserCompanyRole (si Option 2)
   ```
   - Ajouter tous les champs
   - Configurer les relations
   - Ajouter les méthodes helper
   - Tests unitaires des entités

2. **Créer la migration pour Company/BusinessUnit** (1j)
   ```bash
   php bin/console make:migration
   ```
   - Tables `companies`, `business_units`
   - Seed : créer "Default Company" (id=1)
   - Vérifier sur base de dev

3. **Modifier les 45 entités existantes** (8-10j)
   - **Par batch de 10 entités** pour faciliter la review
   - Ajouter `company_id` et `business_unit_id`
   - Ajouter les annotations `@ORM\ManyToOne`, `@ORM\JoinColumn`
   - Mettre `nullable: true` temporairement (le temps de la migration)
   - Ajuster les contraintes UNIQUE
   - Implémenter `CompanyOwnedInterface`

   **Ordre recommandé :**
   1. Core (User, Contributor, EmploymentPeriod, Profile) - 2j
   2. Projects (Project, Client, ClientContact) - 1j
   3. Orders (Order, OrderSection, OrderLine, OrderPaymentSchedule) - 2j
   4. Time (Timesheet, ProjectTask, ProjectSubTask, Planning) - 2j
   5. Config + Analytics (Technology, ServiceCategory, CompanySettings, Fact*, Dim*) - 2j
   6. Notifications, RH, Futurs (Notification, Vacation, etc.) - 1j

4. **Créer les migrations pour chaque batch** (2j)
   ```bash
   php bin/console make:migration
   ```
   - `ALTER TABLE projects ADD company_id INT NULL;`
   - `UPDATE projects SET company_id = 1;` (migrer vers Default Company)
   - `ALTER TABLE projects MODIFY company_id INT NOT NULL;`
   - `ALTER TABLE projects ADD CONSTRAINT fk_company FOREIGN KEY ...`
   - Tests sur base de dev

5. **Créer les fixtures de test multi-tenant** (1j)
   ```php
   // fixtures/CompanyFixtures.php
   $company1 = new Company();
   $company1->setName('Agence Alpha');
   $company1->setSlug('agence-alpha');

   $company2 = new Company();
   $company2->setName('ESN Beta');
   $company2->setSlug('esn-beta');

   // Créer des projets pour chaque company
   ```

6. **Tests d'intégrité** (1j)
   - Vérifier que toutes les entités ont bien `company_id`
   - Vérifier les contraintes UNIQUE (company_id dans l'index)
   - Vérifier les CASCADE DELETE

---

### Phase 3 : Authentication & Context (Semaine 6, 5-6 jours)

#### Objectif
Implémenter le système d'authentification multi-tenant et le contexte Company.

#### Tâches

1. **Modifier User entity** (1j)
   - Ajouter relation User → Company
   - Mettre à jour fixtures
   - Migration pour ajouter `company_id` sur `users` table

2. **Créer CompanyContext service** (1j)
   ```php
   services:
       App\Security\CompanyContext:
           arguments:
               $security: '@security.helper'
               $requestStack: '@request_stack'
   ```

3. **Créer CompanyVoter** (1j)
   - Vérifier que User.company === Subject.company
   - Tester avec plusieurs scenarios (same company, different company)

4. **Modifier Security config** (0.5j)
   - Ajouter `CompanyVoter` dans access_control
   - Tester déni d'accès cross-company

5. **Créer des tests fonctionnels d'isolation** (1.5j)
   ```php
   // Créer 2 companies avec 2 users
   $userAlpha = $this->createUser($companyAlpha);
   $userBeta = $this->createUser($companyBeta);

   // Login userAlpha
   $this->client->loginUser($userAlpha);

   // Tenter d'accéder à un projet de companyBeta
   $this->client->request('GET', '/projects/'.$projectBeta->getId());
   $this->assertResponseStatusCodeSame(403); // Forbidden
   ```

6. **Documentation sécurité** (1j)
   - Guidelines pour développeurs
   - Checklist : "Comment ajouter une nouvelle entité multi-tenant"

---

### Phase 4 : Repository Scoping (Semaine 7-8, 10-12 jours)

#### Objectif
Modifier tous les repositories pour filtrer par company.

#### Tâches

1. **Créer CompanyAwareRepository base class** (1j)
   - Méthode `createCompanyQueryBuilder()`
   - Méthode `findAllForCurrentCompany()`
   - Tests unitaires

2. **Modifier les 36 repositories existants** (8-10j)
   - **Par batch de 5 repositories**
   - Hériter de `CompanyAwareRepository`
   - Remplacer `createQueryBuilder()` par `createCompanyQueryBuilder()`
   - Vérifier toutes les méthodes custom
   - Tests unitaires pour chaque repository

   **Ordre recommandé :**
   1. Core (ContributorRepository, EmploymentPeriodRepository, ProfileRepository) - 2j
   2. Projects (ProjectRepository, ClientRepository, ClientContactRepository) - 2j
   3. Orders (OrderRepository, OrderLineRepository) - 2j
   4. Time (TimesheetRepository, ProjectTaskRepository, PlanningRepository) - 2j
   5. Analytics + Config (TechnologyRepository, ServiceCategoryRepository, Fact/Dim repos) - 2j

3. **Audit de toutes les requêtes DQL/SQL** (1j)
   - Grep pour `createQueryBuilder`, `findBy`, `findOneBy`
   - S'assurer qu'aucune requête ne bypass le filtre company

4. **Tests d'isolation au niveau repository** (1j)
   ```php
   // Test : ProjectRepository ne retourne que les projets de la company
   $companyAlpha = $this->createCompany('Alpha');
   $companyBeta = $this->createCompany('Beta');

   $projectAlpha = $this->createProject($companyAlpha);
   $projectBeta = $this->createProject($companyBeta);

   // Mock CompanyContext pour retourner companyAlpha
   $projects = $this->projectRepo->findAll();

   $this->assertCount(1, $projects);
   $this->assertEquals($projectAlpha->getId(), $projects[0]->getId());
   ```

---

### Phase 5 : Controllers & Services (Semaine 9-10, 8-10 jours)

#### Objectif
Injecter CompanyContext dans tous les controllers et services.

#### Tâches

1. **Identifier tous les controllers qui accèdent aux données** (0.5j)
   - Grep pour `ProjectRepository`, `ClientRepository`, etc.
   - Liste : ~40 controllers

2. **Modifier les controllers** (6-7j)
   - **Par batch de 10 controllers**
   - Injecter `CompanyContext`
   - Appeler `$company = $companyContext->getCurrentCompany()`
   - Passer `$company` aux repositories/services
   - Vérifier les formulaires (ajout automatique de `company` sur création)

   **Exemple :**
   ```php
   // AVANT
   public function index(ProjectRepository $repo): Response
   {
       $projects = $repo->findAll();
       // ...
   }

   // APRÈS
   public function index(CompanyContext $ctx, ProjectRepository $repo): Response
   {
       $company = $ctx->getCurrentCompany();
       $projects = $repo->findAllForCurrentCompany(); // Utilise CompanyContext en interne
       // ...
   }
   ```

3. **Modifier les services métier** (1.5j)
   - Services qui calculent des métriques, KPIs, profitabilité
   - Ajouter filtre company
   - Tests unitaires

4. **Tests fonctionnels E2E** (1j)
   - Parcours complet utilisateur (login → create project → add time → view dashboard)
   - Vérifier qu'aucune donnée ne fuite entre companies

---

### Phase 6 : API & Frontend (Semaine 11, 5-6 jours)

#### Objectif
Adapter l'API REST et le frontend pour multi-tenant.

#### Tâches

1. **Modifier JWT payload** (1j)
   ```json
   {
     "sub": "user_id",
     "email": "john@example.com",
     "company_id": "42",
     "company_slug": "agence-alpha",
     "roles": ["ROLE_MANAGER"]
   }
   ```
   - Ajouter `company_id` dans le token
   - Modifier `JWTCreatedListener`

2. **Modifier API Platform resources** (2j)
   - Ajouter filtres company sur tous les `@ApiResource`
   - Vérifier sécurité (user ne peut accéder qu'à sa company)
   - Tests API avec 2 companies

3. **Adapter le frontend (Twig)** (1j)
   - Afficher le nom de la company dans le header
   - Ajouter sélecteur de BU si applicable
   - Logo company dans la navbar

4. **Créer une page de sélection de company** (0.5j)
   - Si Option 2 (multi-company), permettre de switch
   - Sinon, skip

5. **Tests E2E avec Panther** (1.5j)
   - Tester isolation cross-company
   - Tester switch BU (si applicable)

---

### Phase 7 : Business Units (Semaine 12, 4-5 jours)

#### Objectif
Implémenter le système de Business Units (optionnel mais recommandé).

#### Tâches

1. **Créer les CRUD pour BusinessUnit** (1j)
   ```bash
   php bin/console make:crud BusinessUnit
   ```
   - Formulaire avec parent (arbre de BU)
   - Affichage hiérarchique dans la liste

2. **Ajouter filtre BU aux dashboards** (1j)
   - Dashboard analytics : filtre par BU
   - Dashboard staffing : filtre par BU
   - Dashboard profitability : filtre par BU

3. **Permissions BU** (1j)
   - Manager de BU : accès total à sa BU
   - User standard : accès limité à sa BU
   - Direction : vue consolidée toutes BU

4. **Tests fonctionnels BU** (1j)
   - Créer 2 BU dans 1 company
   - User BU1 ne voit pas les données BU2

---

### Phase 8 : Testing & Validation (Semaine 13, 7-8 jours)

#### Objectif
S'assurer de l'isolation complète et de l'absence de régressions.

#### Tâches

1. **Tests d'isolation exhaustifs** (2j)
   - Créer 3 companies avec données complètes
   - Vérifier qu'aucune donnée ne fuite (projets, timesheets, users, etc.)
   - Tester DELETE d'une company (cascade doit tout supprimer)

2. **Tests de performance** (1j)
   - Benchmarker les requêtes avec/sans filtre company
   - Vérifier que les indexes sont bien utilisés (EXPLAIN queries)
   - Optimiser si besoin (ajout d'index composites)

3. **Tests de charge** (1j)
   - Simuler 10 companies avec 50 users chacune
   - Simuler saisie de temps concurrente
   - Vérifier pas de deadlock/timeout

4. **Audit de sécurité** (1j)
   - Revoir tous les Voters
   - Grep pour `createQueryBuilder` sans filtre company
   - Penetration testing manuel (tenter d'accéder aux données d'une autre company)

5. **Migration de données réelles** (1j)
   - Tester la migration sur une copie de la base de production
   - Vérifier que toutes les données sont bien migrées vers "Default Company"
   - Vérifier pas de perte de données

6. **Documentation utilisateur** (1j)
   - Guide admin : Comment créer une nouvelle company
   - Guide admin : Comment gérer les Business Units
   - Guide user : Comment inviter des collaborateurs

---

### Phase 9 : Déploiement & Monitoring (Semaine 14, 3-4 jours)

#### Objectif
Déployer en production avec rollback plan.

#### Tâches

1. **Déploiement en staging** (0.5j)
   - Tester migration sur staging
   - Vérifier comportement

2. **Rollback plan** (0.5j)
   - Scripts pour revenir en arrière si problème
   - Backup de la base avant migration

3. **Déploiement en production** (1j)
   - **Fenêtre de maintenance** : 2h off-peak
   - Backup complet de la base
   - Exécution des migrations
   - Tests de smoke (login, créer projet, saisir temps)
   - Rollback si problème critique

4. **Monitoring** (1j)
   - Surveiller les logs (erreurs 403, 500)
   - Surveiller la performance des requêtes
   - Alertes si temps de réponse > 500ms

5. **Hotfixes** (1j buffer)
   - Corriger bugs mineurs découverts post-déploiement

---

## Risques & Mitigation

### Risques Techniques

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|-----------|
| **Data leakage cross-tenant** | 🔴 Élevée | 🔴 Critique | Tests d'isolation exhaustifs, code review systématique, audit de sécurité |
| **Oubli de filtre company dans une requête** | 🔴 Élevée | 🔴 Critique | Linter custom, grep automatisé dans CI, tests unitaires par repository |
| **Performance dégradée** | 🟡 Moyenne | 🟡 Moyenne | Indexer (company_id, status), benchmarker avant/après, EXPLAIN queries |
| **Migration échoue sur prod** | 🟡 Moyenne | 🔴 Critique | Tester sur copie de prod, backup complet, rollback plan détaillé |
| **Cascade DELETE supprime trop de données** | 🟢 Faible | 🔴 Critique | Tests sur copie de prod, soft-delete optionnel pour companies |
| **Bugs dans les Voters** | 🟡 Moyenne | 🟡 Moyenne | Tests unitaires des voters, penetration testing |

### Risques Fonctionnels

| Risque | Probabilité | Impact | Mitigation |
|--------|-------------|--------|-----------|
| **Confusion utilisateur (BU)** | 🟡 Moyenne | 🟢 Faible | UX clair, tutoriel onboarding, documentation |
| **Mauvaise estimation du scope** | 🟡 Moyenne | 🟡 Moyenne | Décomposer en petites tâches, revue hebdo |
| **Besoin de multi-company (Option 2) pendant le dev** | 🟢 Faible | 🟡 Moyenne | Commencer avec Option 1, refactorer vers Option 2 si besoin |
| **Régression fonctionnelle** | 🟡 Moyenne | 🟡 Moyenne | Tests E2E complets, test manuel sur tous les parcours |

---

## Estimation Détaillée

### Par Phase

| Phase | Description | Durée | Charge |
|-------|-------------|-------|--------|
| **Phase 1** | Préparation & Design | 2 semaines | 5-7 jours |
| **Phase 2** | Database & Models | 3 semaines | 15-18 jours |
| **Phase 3** | Authentication & Context | 1 semaine | 5-6 jours |
| **Phase 4** | Repository Scoping | 2 semaines | 10-12 jours |
| **Phase 5** | Controllers & Services | 2 semaines | 8-10 jours |
| **Phase 6** | API & Frontend | 1 semaine | 5-6 jours |
| **Phase 7** | Business Units | 1 semaine | 4-5 jours |
| **Phase 8** | Testing & Validation | 1 semaine | 7-8 jours |
| **Phase 9** | Déploiement & Monitoring | 1 semaine | 3-4 jours |
| **TOTAL** | | **14 semaines** | **45-55 jours** |

### Par Compétence

| Compétence | Tâches | Charge |
|------------|--------|--------|
| **Architecture / Design** | Conception entités, design API, plan migration | 6-8 jours |
| **Backend (Entities, Repos, Services)** | Modification des 45 entités, 36 repositories | 25-30 jours |
| **Security (Auth, Voters)** | CompanyContext, Voters, JWT | 5-6 jours |
| **Testing** | Tests unitaires, fonctionnels, E2E, charge | 7-8 jours |
| **DevOps / Migration** | Migrations DB, déploiement, monitoring | 4-5 jours |

**Profil recommandé :** 1 développeur full-stack Symfony senior avec expérience multi-tenant

---

## Recommandations

### Court Terme (Q1 2025)

1. **Ne PAS commencer tout de suite**
   - Finir d'abord les lots prioritaires (Facturation, RGPD, Analytics)
   - Stabiliser l'application mono-tenant avant de complexifier

2. **Valider le besoin SAAS avec le business**
   - Combien de clients potentiels ?
   - Quel pricing model (par user, par projet, forfait) ?
   - ROI attendu vs coût de développement (50j = ~40-50k€)

### Moyen Terme (Q2-Q3 2025)

1. **Démarrer Phase 1 (Design)**
   - Pendant que les autres lots avancent
   - Pas d'impact sur le code existant

2. **Préparer les stakeholders**
   - Présenter l'architecture cible
   - Valider les choix techniques
   - Obtenir buy-in de toute l'équipe

### Long Terme (Q4 2025 - Q1 2026)

1. **Développer par phases**
   - Phases 2-4 : Core multi-tenant (Database, Auth, Repos)
   - Phases 5-7 : Application layer (Controllers, API, BU)
   - Phases 8-9 : Testing & Déploiement

2. **Lancer en beta fermée**
   - 2-3 clients pilotes
   - Retour d'expérience
   - Ajustements avant ouverture publique

---

## Conclusion

La transformation SAAS multi-tenant de HotOnes est **faisable** mais représente un **investissement conséquent** (45-55 jours). L'architecture actuelle est bien structurée, ce qui facilite la migration, mais l'ajout de `company_id` sur 45 entités et le scoping de 36 repositories nécessite **rigueur et tests exhaustifs**.

**Points clés :**
- ✅ Architecture cible claire et éprouvée
- ✅ Plan de migration détaillé et dérisqué
- ⚠️ Charge importante (3 mois pour 1 dev senior)
- ⚠️ Risque de data leakage (mitigé par tests + voters)
- 🎯 ROI dépend du business model SAAS

**Next steps :**
1. Valider le besoin business (nombre de clients, pricing)
2. Prioriser dans la roadmap (après conformité RGPD/Facturation)
3. Commencer par Phase 1 (Design) en parallèle des autres lots
4. Décider Go/No-Go avant Phase 2 (Database)

---

**Auteur** : Claude Code
**Date** : 17 décembre 2024
**Version** : 1.0
