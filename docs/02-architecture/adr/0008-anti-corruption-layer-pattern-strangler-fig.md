# ADR-0008 — Anti-Corruption Layer pattern for DDD/Legacy bridge

**Status**: Accepted
**Date**: 2026-05-06
**Author**: Claude Opus 4.7 (1M context)
**Sprint**: sprint-009 — ACL-ADR-001 (1 pt)

---

## Context

Sprint-008 a livré l'EPIC-001 Phase 1 (3 BCs DDD additifs Client/Project/Order). Coexistence avec Entity flat documentée dans ADR-0005, ADR-0006, ADR-0007.

Phase 2 démarre (sprint-009+): pattern **Strangler Fig** (Martin Fowler) pour migrer progressivement le code legacy vers DDD. Élément clé du pattern: l'**Anti-Corruption Layer (ACL)** entre les deux modèles.

**Problème à résoudre**:
- Code legacy (controllers, services, repositories) utilise `App\Entity\Client` (flat).
- Nouveau code use case veut utiliser `App\Domain\Client\Entity\Client` (DDD, immutable, state machines, domain events).
- Sans ACL: chaque use case devrait connaître les 2 modèles → couplage fort, fragilité.
- Avec ACL: un use case n'utilise QUE le modèle DDD, l'ACL traduit vers/depuis le modèle flat de manière isolée.

## Decision

**Implémenter un Anti-Corruption Layer par BC** sous forme d'**adapter Repository** qui:
1. Implémente le `RepositoryInterface` du domaine (DDD pure)
2. Délègue à un `LegacyRepository` flat existant
3. Convertit les entities flat → DDD à la lecture (via Translators)
4. Convertit les entities DDD → flat à l'écriture (via Translators)
5. Préserve les invariants DDD (state machines, domain events recordés, value objects validés)

### Structure conceptuelle

```
src/
├── Domain/
│   └── Client/
│       ├── Entity/Client.php (DDD, immutable)
│       └── Repository/ClientRepositoryInterface.php (Domain port)
│
├── Application/
│   └── Client/
│       └── UseCase/
│           └── CreateClient/
│               ├── CreateClientUseCase.php
│               └── CreateClientCommand.php
│
└── Infrastructure/
    └── Client/
        ├── Persistence/
        │   └── Doctrine/
        │       └── DoctrineClientRepository.php (ACL adapter)
        └── Translator/
            ├── ClientFlatToDddTranslator.php
            └── ClientDddToFlatTranslator.php
```

### Flux d'un use case (CreateClient exemple)

```
Controller
  ↓
CreateClientUseCase (Application Layer)
  ↓ utilise
ClientRepositoryInterface (Domain Port)
  ↓ implémenté par
DoctrineClientRepository (ACL Adapter)
  ↓ délègue à
App\Repository\ClientRepository (Legacy flat repository)
  ↓ persiste
App\Entity\Client (Flat entity, table `clients`)
```

À la lecture (find()):
1. ACL appelle legacy repo → reçoit `App\Entity\Client`
2. ACL appelle `ClientFlatToDddTranslator::translate(flatEntity)` → reçoit `App\Domain\Client\Entity\Client`
3. Use case manipule l'aggregate DDD (state machine, events)

À l'écriture (save()):
1. Use case passe `App\Domain\Client\Entity\Client` à `ClientRepositoryInterface::save()`
2. ACL appelle `ClientDddToFlatTranslator::translate(dddEntity)` → met à jour ou crée `App\Entity\Client`
3. ACL délègue au legacy repo → persistance via Doctrine annotations existantes

### Translators

Translators sont **sans état** (services), responsables uniquement de la conversion bi-directionnelle. Tests unitaires faciles (input → output).

Exemple `ClientFlatToDddTranslator`:

```php
final class ClientFlatToDddTranslator
{
    public function translate(\App\Entity\Client $flat): \App\Domain\Client\Entity\Client
    {
        // Reconstitution depuis le flat (avec mapping ServiceLevel divergent)
        $clientId = ClientId::fromString((string) $flat->getId());
        $name = CompanyName::fromString($flat->getName());

        // Mapping flat 4 cases → DDD 3 cases (cf ADR-0005)
        $serviceLevel = match($flat->getServiceLevel()) {
            'VIP', 'Prioritaire' => ServiceLevel::ENTERPRISE,
            'Standard' => ServiceLevel::PREMIUM,
            'Basse priorité' => ServiceLevel::STANDARD,
            default => ServiceLevel::STANDARD,
        };

        // Reconstitution via reflection (constructor private)
        // ou via factory `Client::reconstitute()` à ajouter au DDD
        return Client::reconstitute(
            id: $clientId,
            name: $name,
            serviceLevel: $serviceLevel,
            // ... autres props
        );
    }
}
```

### Domain Events à la write

Quand un use case appelle `repository->save($dddClient)`, l'ACL doit:
1. Pull `$dddClient->pullDomainEvents()`
2. Dispatcher sur le bus Symfony Messenger (ou dispatcher direct selon le contexte)
3. Sauver l'entity flat

Cela permet aux events DDD d'être consommés par des handlers (audit, notifications, integrations) sans coupler le legacy.

## Consequences

**Positives**:
- Use case lit/écrit en DDD pur — code lisible, invariants garantis
- Code legacy reste fonctionnel pendant la migration
- Translators isolent la complexité — testables unitairement
- Domain Events disponibles pour intégrations
- Migration progressive: 1 use case à la fois

**Négatives**:
- Coût d'écriture initial: 2 translators + 1 repository adapter par BC
- Translation bi-directionnelle = duplication potentielle de logique de validation
- Risque de sur-ingénierie pour des CRUD simples

**Mitigation**:
- Génériques: créer une classe abstraite `AbstractDoctrineDddRepository` réutilisable
- Code generation possible (sprint futur) pour les translators basiques
- ADR documente le when-to-use: ACL pour BCs complexes, lecture-seule via DTO suffisant pour CRUD simples

## When NOT to use this pattern

- **Read-only views**: utilisez un Read-Model DTO direct, pas besoin d'aggregate DDD
- **Simple CRUD without business rules**: garder l'Entity flat, pas de DDD nécessaire
- **API Platform endpoints**: ces endpoints utilisent déjà l'Entity flat avec serialization groups → ne pas ré-écrire

## Roadmap

| Sprint | Action |
|---|---|
| **009 (ICI)** | Documentation pattern (cette ADR) + 1ère implémentation Client BC |
| 009-010 | Project + Order BCs ACL (1 par sprint) |
| 010-011 | Migration progressive controllers `ClientController`, `ProjectController`, etc. |
| 011-012 | Décommissionnement Entity flat quand zéro référence |

## Tests requis pour valider le pattern

Pour chaque ACL adapter:
- Tests Unit Translators (flat→DDD et DDD→flat round-trip)
- Tests Unit Repository ACL (avec mock du legacy repo)
- 1 test Functional du use case (de bout en bout)

## References

- **Strangler Fig pattern** — Martin Fowler (https://martinfowler.com/bliki/StranglerFigApplication.html)
- **Anti-Corruption Layer** — Eric Evans, *Domain-Driven Design*
- **EPIC-001** — Migration Clean Architecture + DDD
- **ADR-0005/0006/0007** — BCs DDD coexistence (Phase 1)

---

**Approved**: branche `feat/acl-adr-001-anti-corruption-layer-pattern`, PR #137.
