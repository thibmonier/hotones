# Sprint 4 - Abstractions : Use Cases (CQRS)

> **Durée:** 2 semaines
> **Priorité:** HAUTE
> **Phase:** 2/4 - Abstractions

---

## Sprint Goal

Implémenter les Use Cases avec le pattern CQRS (Command/Query Responsibility Segregation).

---

## Contexte

Les Repositories sont en place. Il faut maintenant créer la couche Application avec Commands, Queries et Handlers.

---

## User Stories

### US-018: Créer la structure CQRS

**En tant que** développeur
**Je veux** une structure Command/Query dans Application
**Afin de** séparer lectures et écritures

**Critères d'acceptance:**
- [ ] Structure `Application/{BC}/Command/` créée
- [ ] Structure `Application/{BC}/Query/` créée
- [ ] Structure `Application/{BC}/Handler/` créée
- [ ] DTOs pour les réponses Query

**Points:** 2

---

### US-019: Implémenter CreateReservationUseCase

**En tant que** développeur
**Je veux** un Use Case pour créer une réservation
**Afin de** orchestrer la logique de création

**Critères d'acceptance:**
- [ ] `CreateReservationCommand` créé
- [ ] `CreateReservationHandler` implémenté
- [ ] Validation métier dans le Handler
- [ ] Tests unitaires passent

**Points:** 5

---

### US-020: Implémenter ConfirmReservationUseCase

**En tant que** développeur
**Je veux** un Use Case pour confirmer une réservation
**Afin de** gérer le workflow de confirmation

**Critères d'acceptance:**
- [ ] `ConfirmReservationCommand` créé
- [ ] `ConfirmReservationHandler` implémenté
- [ ] Dispatch des Domain Events
- [ ] Tests unitaires passent

**Points:** 3

---

### US-021: Implémenter GetReservationQuery

**En tant que** développeur
**Je veux** une Query pour récupérer une réservation
**Afin de** exposer les données en lecture

**Critères d'acceptance:**
- [ ] `GetReservationQuery` créé
- [ ] `GetReservationHandler` implémenté
- [ ] `ReservationDTO` pour la réponse
- [ ] Tests unitaires passent

**Points:** 3

---

### US-022: Configurer Symfony Messenger

**En tant que** développeur
**Je veux** Messenger configuré pour les handlers
**Afin de** dispatcher Commands et Queries

**Critères d'acceptance:**
- [ ] Configuration `messenger.yaml` mise à jour
- [ ] Bus Command configuré
- [ ] Bus Query configuré
- [ ] Tests fonctionnels passent

**Points:** 3

---

## Total Points: 16

---

## Definition of Done

- [ ] Commands dans `src/Application/{BC}/Command/`
- [ ] Queries dans `src/Application/{BC}/Query/`
- [ ] Handlers avec `#[AsMessageHandler]`
- [ ] DTOs immutables (`final readonly`)
- [ ] `make phpstan` passe
- [ ] `make test-unit` passe
- [ ] Couverture tests > 80%

---

## Dépendances

- Sprint 1 (structure)
- Sprint 2 (Value Objects)
- Sprint 3 (Repositories)

---

## Risques

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Complexité CQRS | Moyen | Documentation claire |
| Performance bus | Faible | Sync par défaut |
