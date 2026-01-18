# EPIC-002: Implémentation des Value Objects

**Statut**: 📋 Backlog
**Priorité**: 🔴 CRITIQUE
**Effort Estimé**: 1-2 sprints (Phase 1)
**Business Value**: 🟡 ÉLEVÉ
**Risque Technique**: 🟢 FAIBLE

---

## Vue d'ensemble

Créer tous les **Value Objects immuables** nécessaires pour remplacer les types primitifs dans le Domain Layer. Cette implémentation peut être faite **en parallèle** de EPIC-001 (Phase 1).

### Problème adressé

**Audit Report - Problem #3**: Absence de Value Objects
- **Score actuel**: Entités et Value Objects 2/5 ⚠️
- **Impact**: Validation dispersée, duplication de code, types primitifs non sûrs
- **Fichiers concernés**: `src/Entity/Client.php`, toutes les entités

### Solution proposée

Création de Value Objects immuables (`final readonly`) pour :

```php
src/Domain/Shared/ValueObject/
├── Email.php                 # Validation email
├── PhoneNumber.php           # Validation téléphone + formats pays
├── PostalAddress.php         # Adresse complète
├── PersonName.php            # Nom + prénom
├── Money.php                 # Montant en centimes + devise
├── ClientId.php              # UUID typé pour Client
├── UserId.php                # UUID typé pour User
├── OrderId.php               # UUID typé pour Order
├── ReservationId.php         # UUID typé pour Reservation
└── TenantId.php              # UUID typé pour multitenant
```

---

## Objectifs métier

### Bénéfices attendus

1. **Type safety améliorée**
   - Impossible de passer un string à la place d'un Email
   - Détection erreurs à la compilation (PHPStan niveau max)
   - Pas de valeurs primitives dans le Domain

2. **Validation centralisée**
   - Règle d'or: **Un seul endroit** pour valider un email
   - Pas de duplication de validation
   - Fail-fast au moment de la création

3. **Immutabilité garantie**
   - `readonly` enforce l'immutabilité
   - Pas d'effets de bord accidentels
   - Thread-safe par design

4. **Réduction des bugs de 20%**
   - Validation à la création (pas de valeurs invalides possibles)
   - Type hints stricts empêchent les erreurs de type
   - Tests unitaires simples et rapides

---

## Exigences liées

- **REQ-003**: Value Objects Immuables

---

## User Stories associées

### Phase 1: Value Objects de base (Sprint 1)

- **US-010**: Créer Value Object Email avec validation RFC 5322
- **US-011**: Créer Value Object PhoneNumber avec formats internationaux
- **US-012**: Créer Value Object Money (centimes + devise)
- **US-013**: Créer Value Objects IDs typés (ClientId, UserId, OrderId)
- **US-014**: Créer Value Object PersonName (nom + prénom)

### Phase 1: Value Objects métier (Sprint 2)

- **US-015**: Créer Value Object PostalAddress avec validation par pays
- **US-016**: Créer Value Object TenantId pour multitenant
- **US-017**: Créer Doctrine Custom Types pour tous les VOs
- **US-018**: Remplacer types primitifs par VOs dans entités Domain

---

## Critères d'acceptation (EPIC)

### Value Objects créés

- [ ] Email VO avec validation RFC 5322
- [ ] PhoneNumber VO avec formats pays (FR, DE, ES, IT, NL, BE, EN)
- [ ] Money VO avec centimes + devise (EUR, GBP)
- [ ] PersonName VO avec nom + prénom
- [ ] PostalAddress VO avec validation codes postaux par pays
- [ ] Tous les IDs typés (ClientId, UserId, OrderId, ReservationId, TenantId)

### Caractéristiques obligatoires

- [ ] Tous les VOs sont `final readonly class`
- [ ] Validation dans le constructeur (fail-fast)
- [ ] Factory method statique `fromString()` ou `fromXxx()`
- [ ] Méthode `equals()` pour comparaison par valeur
- [ ] Méthode `getValue()` ou getters spécifiques
- [ ] Méthode `__toString()` si pertinent
- [ ] **Aucun setter** (immutabilité)

### Doctrine Integration

- [ ] Doctrine Custom Type créé pour chaque VO
- [ ] Types enregistrés dans `config/packages/doctrine.yaml`
- [ ] Mappings XML utilisent les custom types

### Tests

- [ ] Tests unitaires couvrant la validation (edge cases)
- [ ] Tests unitaires pour méthodes métier (`add`, `multiply`, etc.)
- [ ] Tests unitaires pour `equals()`
- [ ] Couverture code ≥ 90% sur tous les VOs
- [ ] Tests rapides (< 50ms par VO)

### Documentation

- [ ] PHPDoc pour chaque VO (format attendu, exemples)
- [ ] Exemples d'usage dans `.claude/examples/value-object-examples.md`
- [ ] ADR justifiant l'usage des VOs

---

## Métriques de succès

| Métrique | Avant | Cible | Validation |
|----------|-------|-------|------------|
| **Types primitifs dans Domain** | 100% | 0% | Grep dans src/Domain/ |
| **Validation centralisée** | Non | Oui | 1 seule validation par concept |
| **Couverture VOs** | N/A | ≥ 90% | `make test-coverage` |
| **Bugs validation** | Non mesuré | -20% | Issue tracker |
| **PHPStan errors types** | Non mesuré | 0 | `make phpstan` |

---

## Dépendances

### Bloquantes (doivent être faites avant)

- Aucune (peut être fait en parallèle EPIC-001 Phase 1)

### Bloquées par cet EPIC

- **EPIC-001**: Extraction entités Domain (US-002, US-004, US-006) → nécessite VOs disponibles
- **EPIC-003**: Repository Abstraction → utilise les IDs typés
- **EPIC-004**: Domain Services → utilise Money, Email, etc.

---

## Risques et mitigations

### Risque 1: Complexité Doctrine Custom Types

- **Probabilité**: Faible
- **Impact**: Moyen
- **Mitigation**:
  - Suivre template de `.claude/rules/18-value-objects.md`
  - Commencer par Email (le plus simple)
  - Tests d'intégration pour chaque custom type

### Risque 2: Prolifération de Value Objects

- **Probabilité**: Moyenne
- **Impact**: Faible
- **Mitigation**:
  - Créer uniquement les VOs **réellement utiles**
  - Règle: Si utilisé dans 3+ endroits → créer VO
  - Revue régulière pour éviter over-engineering

### Risque 3: Migration entités existantes

- **Probabilité**: Faible
- **Impact**: Faible
- **Mitigation**:
  - Migration progressive avec tests à chaque étape
  - Rollback possible via Git
  - Tests fonctionnels garantissent non-régression

---

## Approche d'implémentation

### Stratégie: Test-Driven Development (TDD)

1. **Écrire le test (RED)** pour un Value Object
2. **Implémenter le VO** pour passer le test (GREEN)
3. **Refactorer** si nécessaire (REFACTOR)
4. **Créer le Doctrine Custom Type** pour persistance
5. **Tester l'intégration Doctrine**

### Ordre de création recommandé

1. **Email** (simple, pas de dépendances) ✅ Prioritaire
2. **Money** (utilisé partout, critical)
3. **PersonName** (utilisé Client, User)
4. **ClientId, UserId, OrderId** (IDs typés)
5. **PhoneNumber** (dépend de Country enum - i18n)
6. **PostalAddress** (dépend de Country, PhoneNumber)
7. **TenantId** (multitenant)
8. **ReservationId** (pour EPIC suivants)

### Template Value Object (Email)

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

final readonly class Email
{
    private const string PATTERN = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';

    private function __construct(
        private string $value
    ) {
        $this->validate();
    }

    public static function fromString(string $value): self
    {
        return new self(strtolower(trim($value)));
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getDomain(): string
    {
        return explode('@', $this->value)[1];
    }

    public function equals(Email $other): bool
    {
        return $this->value === $other->value;
    }

    private function validate(): void
    {
        if (preg_match(self::PATTERN, $this->value) !== 1) {
            throw new \InvalidArgumentException(
                sprintf('Invalid email address: %s', $this->value)
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
```

### Template Doctrine Custom Type

```php
<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Type;

use App\Domain\Shared\ValueObject\Email;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\StringType;

final class EmailType extends StringType
{
    public const string NAME = 'email';

    public function convertToPHPValue($value, AbstractPlatform $platform): ?Email
    {
        if ($value === null) {
            return null;
        }

        return Email::fromString($value);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!$value instanceof Email) {
            throw ConversionException::conversionFailedInvalidType(
                $value,
                $this->getName(),
                ['null', Email::class]
            );
        }

        return $value->getValue();
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
```

### Validation continue

- À chaque Value Object créé:
  - [ ] Tests unitaires passent (validation, equals, getValue)
  - [ ] PHPStan niveau max passe
  - [ ] Doctrine Custom Type testé en intégration
  - [ ] Exemple d'usage documenté

---

## Références

### Documentation interne

- `.claude/rules/18-value-objects.md` - Template et exemples complets
- `.claude/rules/16-i18n.md` - Country enum et formats (lignes 13-46)
- `.claude/examples/value-object-examples.md` - Exemples Money, Email, DateRange
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Audit source (lignes 75-108, 277-322)

### Checklist Phase 1 (Audit Report)

**Semaine 1-2** (lignes 380-384):
- [ ] Créer la structure Domain/Application/Infrastructure/Presentation - **EPIC-001**
- [ ] Extraire les entités Domain (sans annotations Doctrine) - **EPIC-001**
- [x] **Créer les premiers Value Objects (Email, Money, IDs)** - **EPIC-002**
- [ ] Créer les mappings Doctrine XML dans Infrastructure - **EPIC-001**

### Ressources externes

- [Value Object Pattern - Martin Fowler](https://martinfowler.com/bliki/ValueObject.html)
- [Doctrine Custom Types](https://www.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html)
- [PHP 8 readonly properties](https://www.php.net/manual/en/language.oop5.properties.php#language.oop5.properties.readonly-properties)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création EPIC | Claude (via workflow-plan) |
| 2026-01-13 | Validation priorité CRITIQUE | Architecture audit score 2/5 VOs |

---

## Notes

- **Prerequis**: Lecture obligatoire de `.claude/rules/18-value-objects.md` avant implémentation
- **TDD obligatoire**: Cycle RED → GREEN → REFACTOR pour chaque Value Object
- **Immutabilité**: Tous les VOs doivent être `final readonly class`
- **Validation**: Fail-fast dans le constructeur (pas de valeurs invalides possibles)
- **Country Support**: PhoneNumber et PostalAddress doivent supporter les 7 pays (FR, EN, DE, ES, IT, NL, BE)
- **Definition of Done**: Voir `/Users/tmonier/Projects/hotones/project-management/prd.md` section "Définition de Done"
