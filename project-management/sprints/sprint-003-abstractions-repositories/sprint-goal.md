# Sprint 3 - Abstractions : Repository Interfaces

> **Durée:** 2 semaines
> **Priorité:** HAUTE
> **Phase:** 2/4 - Abstractions

---

## Sprint Goal

Créer les interfaces Repository dans le Domain et leurs implémentations Doctrine dans l'Infrastructure.

---

## Contexte

Les entités et Value Objects sont en place. Il faut maintenant abstraire la persistance pour respecter le DIP (Dependency Inversion Principle).

---

## User Stories

### US-013: Créer les interfaces Repository du Domain

**En tant que** développeur
**Je veux** des interfaces Repository dans le Domain
**Afin de** découpler le métier de la persistance

**Critères d'acceptance:**
- [ ] `ClientRepositoryInterface` dans Domain
- [ ] `ReservationRepositoryInterface` dans Domain
- [ ] `SejourRepositoryInterface` dans Domain
- [ ] Méthodes: `findById()`, `save()`, `delete()`
- [ ] Exceptions métier définies

**Points:** 3

---

### US-014: Implémenter DoctrineClientRepository

**En tant que** développeur
**Je veux** une implémentation Doctrine du ClientRepository
**Afin de** persister les clients

**Critères d'acceptance:**
- [ ] Implémente `ClientRepositoryInterface`
- [ ] Dans `Infrastructure/Persistence/Doctrine/`
- [ ] Mapping XML pour Client
- [ ] Tests d'intégration passent

**Points:** 3

---

### US-015: Implémenter DoctrineReservationRepository

**En tant que** développeur
**Je veux** une implémentation Doctrine du ReservationRepository
**Afin de** persister les réservations

**Critères d'acceptance:**
- [ ] Implémente `ReservationRepositoryInterface`
- [ ] Mapping XML pour Reservation et Participant
- [ ] Gestion des relations (Aggregate)
- [ ] Tests d'intégration passent

**Points:** 5

---

### US-016: Implémenter DoctrineSejourRepository

**En tant que** développeur
**Je veux** une implémentation Doctrine du SejourRepository
**Afin de** persister les séjours

**Critères d'acceptance:**
- [ ] Implémente `SejourRepositoryInterface`
- [ ] Mapping XML pour Sejour
- [ ] Tests d'intégration passent

**Points:** 3

---

### US-017: Configurer l'injection de dépendances

**En tant que** développeur
**Je veux** que les interfaces soient autowirées aux implémentations
**Afin de** faciliter l'utilisation des repositories

**Critères d'acceptance:**
- [ ] Configuration `services.yaml` mise à jour
- [ ] Interfaces bindées aux implémentations Doctrine
- [ ] Tests fonctionnels vérifient l'autowiring

**Points:** 2

---

## Total Points: 16

---

## Definition of Done

- [ ] Interfaces dans `src/Domain/{BC}/Repository/`
- [ ] Implémentations dans `src/Infrastructure/Persistence/Doctrine/`
- [ ] Mappings XML fonctionnels
- [ ] `make db-validate` passe
- [ ] `make phpstan` passe
- [ ] Tests d'intégration passent

---

## Dépendances

- Sprint 1 (structure)
- Sprint 2 (Value Objects)

---

## Risques

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Mapping XML incorrect | Fort | Validation schéma |
| Performance requêtes | Moyen | Profiling Doctrine |
