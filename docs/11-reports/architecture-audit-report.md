# 🏛️ Audit Architecture Symfony - Rapport Complet

**Date:** 2026-01-13
**Projet:** hotones
**Auditeur:** Claude Code (Symfony Architecture Checker)

---

## 📊 Score Global

**6/25** ⚠️ **ARCHITECTURE NÉCESSITANT REFACTORING MAJEUR**

### Détail par Catégorie

| Catégorie | Score | Max | Statut | Commentaire |
|-----------|-------|-----|--------|-------------|
| **Structure des Couches** | 0 | 5 | ❌ | Aucune séparation Domain/Application/Infrastructure |
| **Séparation des Responsabilités** | 2 | 5 | ⚠️ | Séparation partielle mais couplage fort |
| **Entités et Value Objects** | 2 | 5 | ⚠️ | Entités anémiques, pas de Value Objects |
| **Aggregates et Repositories** | 1 | 5 | ❌ | Pas d'Aggregates, Repositories couplés à Doctrine |
| **Ports (Interfaces)** | 0 | 2.5 | ❌ | Aucune abstraction via interfaces |
| **Adapters (Implémentations)** | 1 | 2.5 | ⚠️ | Implémentations présentes mais mal structurées |
| **Validation Deptrac** | 0 | 5 | ❌ | Configuration présente mais validation échouée |

---

## 🔍 Problèmes Détectés

### ❌ CRITIQUE - Absence d'Architecture en Couches

**Impact:** Maintenance difficile, tests complexes, évolution risquée

**Détails:**
- Aucun répertoire `src/Domain/`, `src/Application/`, `src/Infrastructure/`, `src/Presentation/`
- Structure traditionnelle Symfony (Controller/Entity/Repository/Service) au lieu de Clean Architecture
- Logique métier dispersée sans frontières claires

**Fichiers concernés:**
- Structure complète du projet `src/`

**Recommandation:** Migration complète vers Clean Architecture + DDD

---

### ❌ CRITIQUE - Entités Couplées à l'Infrastructure

**Impact:** Domain non testable indépendamment, dépendance forte à Doctrine

**Détails:**
```php
// src/Entity/Client.php
#[ORM\Entity(repositoryClass: ClientRepository::class)]  // ❌ Doctrine dans Domain
#[ORM\Table(name: 'clients')]
class Client implements Stringable, CompanyOwnedInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    public private(set) ?int $id = null;
```

**Problème:** Les entités contiennent des annotations Doctrine ORM, violant la séparation Domain/Infrastructure.

**Fichiers concernés:**
- `src/Entity/Client.php` (lignes 13-23)
- Toutes les entités dans `src/Entity/`

**Recommandation:**
1. Séparer les entités Domain pures dans `src/Domain/`
2. Créer des mappings Doctrine XML/YAML dans `src/Infrastructure/Persistence/Doctrine/Mapping/`
3. Utiliser des Repository interfaces dans le Domain

---

### ❌ CRITIQUE - Absence de Value Objects

**Impact:** Validation dispersée, duplication de code, types primitifs non sûrs

**Détails:**
- Utilisation de types primitifs (string, int) au lieu de Value Objects typés
- Pas de validation encapsulée dans des objets immuables
- Exemples manquants : Email, Money, PhoneNumber, Address, etc.

**Exemple actuel:**
```php
// src/Entity/Client.php
#[ORM\Column(type: 'string', length: 255)]
public string $name { get; set; }  // ❌ String simple au lieu de PersonName VO

#[ORM\Column(type: 'string', length: 255, nullable: true)]
public ?string $email { get; set; }  // ❌ String simple au lieu de Email VO
```

**Recommandation:** Créer des Value Objects immuables :
```php
// src/Domain/Shared/ValueObject/Email.php
final readonly class Email
{
    public function __construct(private string $value)
    {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email');
        }
    }

    public function getValue(): string { return $this->value; }
}
```

---

### ❌ MAJEUR - Repositories Sans Interfaces

**Impact:** Couplage fort, impossible de tester avec des mocks, violation DIP

**Détails:**
```php
// src/Repository/ClientRepository.php
class ClientRepository extends CompanyAwareRepository  // ❌ Pas d'interface
{
    public function findAllOrderedByName(): array
    {
        return $this->createCompanyQueryBuilder('c')
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
```

**Problème:**
- Pas d'interface `ClientRepositoryInterface` dans le Domain
- Services dépendent directement de l'implémentation Doctrine
- Violation du Dependency Inversion Principle (DIP)

**Fichiers concernés:**
- `src/Repository/ClientRepository.php`
- Tous les repositories dans `src/Repository/`

**Recommandation:**
```php
// src/Domain/Client/Repository/ClientRepositoryInterface.php
interface ClientRepositoryInterface
{
    public function findById(ClientId $id): ?Client;
    public function save(Client $client): void;
}

// src/Infrastructure/Persistence/Doctrine/Repository/DoctrineClientRepository.php
final class DoctrineClientRepository implements ClientRepositoryInterface
{
    // Implémentation Doctrine
}
```

---

### ⚠️ MOYEN - Services Couplés à EntityManager

**Impact:** Tests difficiles, logique métier mélangée avec persistance

**Détails:**
```php
// src/Service/NotificationService.php
public function __construct(
    private readonly EntityManagerInterface $em,  // ❌ Couplage à Doctrine
    private readonly NotificationRepository $notificationRepository,
    // ...
) {}
```

**Problème:** Les services applicatifs injectent directement EntityManagerInterface au lieu de passer par des Repository interfaces.

**Fichiers concernés:**
- `src/Service/NotificationService.php` (ligne 21)
- Autres services dans `src/Service/`

**Recommandation:** Utiliser uniquement des Repository interfaces dans les services.

---

### ⚠️ MOYEN - Absence d'Aggregates

**Impact:** Incohérence des données, transactions mal gérées, invariants non garantis

**Détails:**
- Aucun Aggregate Root identifié
- Pas de délimitation claire des frontières transactionnelles
- Entités modifiées directement sans passer par un Aggregate Root

**Recommandation:** Identifier les Aggregates métier (ex: Reservation avec Participants, Order avec OrderItems).

---

### ⚠️ MINEUR - Deptrac Non Validé

**Impact:** Violations architecturales non détectées automatiquement

**Détails:**
- `deptrac.yaml` présent mais configuré pour architecture traditionnelle
- Validation échouée à cause d'erreurs de syntaxe PHP 8.4
- Ne valide pas les couches Clean Architecture (car elles n'existent pas)

**Erreur rencontrée:**
```
Syntax error, unexpected T_OBJECT_OPERATOR on line 113
in src/Command/ContributorSatisfactionReminderCommand.php
```

**Fichiers concernés:**
- `deptrac.yaml`
- `src/Command/ContributorSatisfactionReminderCommand.php` (ligne 113)

**Recommandation:**
1. Corriger les erreurs de syntaxe
2. Reconfigurer Deptrac pour valider les couches Domain/Application/Infrastructure/Presentation

---

## 🎯 Top 3 Actions Prioritaires

### 1️⃣ PRIORITÉ HAUTE - Restructurer en Clean Architecture

**Impact:** 🔴 **TRÈS ÉLEVÉ** - Fondation de toute l'architecture
**Effort:** 🟠 **ÉLEVÉ** - Plusieurs semaines de refactoring
**Urgence:** ⚠️ **IMMÉDIATE**

**Actions concrètes:**

1. **Créer la structure de répertoires:**
   ```
   src/
   ├── Domain/              # Logique métier pure
   │   ├── Client/
   │   │   ├── Entity/Client.php
   │   │   ├── ValueObject/ClientId.php
   │   │   ├── Repository/ClientRepositoryInterface.php
   │   │   ├── Service/ClientDomainService.php
   │   │   └── Event/ClientCreatedEvent.php
   │   └── Shared/
   │       └── ValueObject/
   │           ├── Email.php
   │           └── PhoneNumber.php
   ├── Application/         # Use Cases
   │   └── Client/
   │       ├── CreateClient/
   │       │   ├── CreateClientCommand.php
   │       │   └── CreateClientHandler.php
   │       └── Query/
   │           └── GetClientQuery.php
   ├── Infrastructure/      # Détails techniques
   │   ├── Persistence/
   │   │   └── Doctrine/
   │   │       ├── Repository/DoctrineClientRepository.php
   │   │       └── Mapping/Client.orm.xml
   │   └── Mailer/
   └── Presentation/        # UI (Controllers, Forms, CLI)
       └── Controller/ClientController.php
   ```

2. **Extraire les entités Domain:**
   - Supprimer les annotations Doctrine des entités
   - Créer des mappings XML/YAML séparés dans Infrastructure

3. **Créer les Repository interfaces:**
   - Interface dans `Domain/Client/Repository/`
   - Implémentation Doctrine dans `Infrastructure/Persistence/Doctrine/Repository/`

**Bénéfices attendus:**
- ✅ Tests unitaires du Domain sans dépendances
- ✅ Changement de framework facilité
- ✅ Séparation claire des responsabilités
- ✅ Code maintenable et évolutif

---

### 2️⃣ PRIORITÉ HAUTE - Créer des Value Objects

**Impact:** 🟡 **ÉLEVÉ** - Qualité du code et sécurité des types
**Effort:** 🟢 **MOYEN** - Quelques jours de développement
**Urgence:** ⚠️ **IMPORTANTE**

**Actions concrètes:**

1. **Identifier les candidats Value Objects:**
   - Email, PhoneNumber, Address
   - Money, Currency
   - ClientId, UserId (typed identifiers)
   - ServiceLevel, Priority (enums/VOs)

2. **Créer les Value Objects immuables:**
   ```php
   // src/Domain/Shared/ValueObject/Email.php
   final readonly class Email
   {
       public function __construct(private string $value)
       {
           if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
               throw new InvalidArgumentException('Invalid email');
           }
       }

       public function getValue(): string { return $this->value; }
       public function equals(self $other): bool { return $this->value === $other->value; }
   }
   ```

3. **Remplacer les types primitifs:**
   ```php
   // Avant
   private string $email;

   // Après
   private Email $email;
   ```

**Bénéfices attendus:**
- ✅ Validation centralisée
- ✅ Type safety
- ✅ Immutabilité garantie
- ✅ Réduction des bugs

---

### 3️⃣ PRIORITÉ MOYENNE - Abstraire les Repositories

**Impact:** 🟡 **MOYEN** - Testabilité et découplage
**Effort:** 🟢 **FAIBLE** - Quelques jours
**Urgence:** 📅 **PLANIFIABLE**

**Actions concrètes:**

1. **Créer les interfaces Repository dans Domain:**
   ```php
   // src/Domain/Client/Repository/ClientRepositoryInterface.php
   interface ClientRepositoryInterface
   {
       public function findById(ClientId $id): ?Client;
       public function findByEmail(Email $email): ?Client;
       public function save(Client $client): void;
       public function delete(Client $client): void;
   }
   ```

2. **Implémenter dans Infrastructure:**
   ```php
   // src/Infrastructure/Persistence/Doctrine/Repository/DoctrineClientRepository.php
   final class DoctrineClientRepository implements ClientRepositoryInterface
   {
       public function __construct(
           private EntityManagerInterface $em
       ) {}

       public function findById(ClientId $id): ?Client
       {
           return $this->em->find(Client::class, $id->getValue());
       }

       // ...
   }
   ```

3. **Modifier les services pour dépendre des interfaces:**
   ```php
   // src/Application/Client/CreateClient/CreateClientHandler.php
   public function __construct(
       private ClientRepositoryInterface $clientRepository  // ✅ Interface
   ) {}
   ```

**Bénéfices attendus:**
- ✅ Tests avec mocks facilités
- ✅ Respect du Dependency Inversion Principle
- ✅ Changement d'implémentation (Doctrine → autre) simplifié

---

## 📋 Checklist de Refactoring

### Phase 1 : Fondations (Semaine 1-2)
- [ ] Créer la structure Domain/Application/Infrastructure/Presentation
- [ ] Extraire les entités Domain (sans annotations Doctrine)
- [ ] Créer les mappings Doctrine XML dans Infrastructure
- [ ] Créer les premiers Value Objects (Email, Money, IDs)

### Phase 2 : Abstractions (Semaine 3-4)
- [ ] Créer toutes les interfaces Repository dans Domain
- [ ] Implémenter les repositories Doctrine dans Infrastructure
- [ ] Refactorer les services pour utiliser les interfaces
- [ ] Créer les Use Cases (Commands/Queries + Handlers)

### Phase 3 : Domain Services et Events (Semaine 5-6)
- [ ] Identifier et créer les Aggregates
- [ ] Extraire les Domain Services
- [ ] Implémenter les Domain Events
- [ ] Ajouter les Event Handlers dans Application

### Phase 4 : Validation (Semaine 7)
- [ ] Reconfigurer Deptrac pour Clean Architecture
- [ ] Corriger les violations détectées
- [ ] Atteindre 100% de validation Deptrac
- [ ] Tests d'architecture automatisés

---

## 📚 Ressources Recommandées

### Documentation
- [Clean Architecture - Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Domain-Driven Design - Eric Evans](https://www.domainlanguage.com/ddd/)
- [Hexagonal Architecture - Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)

### Règles Projet
- `.claude/rules/02-architecture-clean-ddd.md` - Architecture obligatoire
- `.claude/rules/04-solid-principles.md` - Principes SOLID
- `.claude/rules/13-ddd-patterns.md` - Patterns DDD détaillés
- `.claude/rules/18-value-objects.md` - Value Objects patterns
- `.claude/rules/19-aggregates.md` - Aggregates et Aggregate Roots

### Outils
- [Deptrac](https://qossmic.github.io/deptrac/) - Validation des dépendances
- [PHPStan](https://phpstan.org/) - Analyse statique niveau max
- [Rector](https://getrector.com/) - Refactoring automatique

---

## 🎓 Conclusion

Le projet **hotones** utilise actuellement une architecture Symfony traditionnelle qui ne respecte pas les principes de Clean Architecture, DDD, et Hexagonal Architecture.

**Score global : 6/25** ⚠️

### Points Positifs
- ✅ Séparation basique Controller/Service/Repository présente
- ✅ Deptrac configuré (même s'il ne valide pas Clean Architecture)
- ✅ Usage de PHP 8.4 moderne (property hooks)
- ✅ Dependency injection correctement utilisée

### Points d'Amélioration Critiques
- ❌ Aucune séparation Domain/Application/Infrastructure
- ❌ Entités couplées à Doctrine
- ❌ Absence de Value Objects
- ❌ Repositories sans abstractions
- ❌ Pas d'Aggregates identifiés

### Recommandation Finale

**REFACTORING ARCHITECTURAL MAJEUR REQUIS**

Une migration progressive vers Clean Architecture + DDD est **fortement recommandée** pour :
- Améliorer la maintenabilité à long terme
- Faciliter les tests unitaires
- Réduire le couplage technique
- Respecter les standards du projet (voir `.claude/rules/02-architecture-clean-ddd.md`)

**Estimation effort total:** 6-8 semaines de refactoring progressif avec l'approche Strangler Fig Pattern (refactoring incrémental sans tout réécrire).

---

**Rapport généré le:** 2026-01-13
**Par:** Claude Code - Symfony Architecture Checker
**Version:** 1.0.0
