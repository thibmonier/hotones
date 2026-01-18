# Sprint 6 - Domain Events

> **Durée:** 2 semaines
> **Priorité:** HAUTE
> **Phase:** 3/4 - Domain Services & Events

---

## Sprint Goal

Implémenter les Domain Events pour découpler les effets de bord et assurer la traçabilité des changements.

---

## Contexte

Les Domain Services sont en place. Il faut maintenant ajouter les Domain Events pour notifier les autres parties du système des changements importants.

---

## User Stories

### US-027: Créer l'infrastructure des Domain Events

**En tant que** développeur
**Je veux** une infrastructure pour les Domain Events
**Afin de** standardiser leur création et dispatch

**Critères d'acceptance:**
- [ ] Interface `DomainEventInterface` créée
- [ ] Trait `RecordsDomainEvents` pour les Aggregates
- [ ] `EventBusInterface` dans Domain
- [ ] Implémentation Messenger dans Infrastructure

**Points:** 3

---

### US-028: Implémenter ReservationCreatedEvent

**En tant que** développeur
**Je veux** un événement lors de la création de réservation
**Afin de** déclencher les actions post-création

**Critères d'acceptance:**
- [ ] Event immutable (`final readonly`)
- [ ] Contient ReservationId, timestamp
- [ ] Handler pour envoi email confirmation
- [ ] Tests unitaires passent

**Points:** 3

---

### US-029: Implémenter ReservationConfirmedEvent

**En tant que** développeur
**Je veux** un événement lors de la confirmation
**Afin de** mettre à jour les statistiques

**Critères d'acceptance:**
- [ ] Event avec données minimales nécessaires
- [ ] Handler async via Messenger
- [ ] Tests unitaires passent

**Points:** 3

---

### US-030: Implémenter ReservationCancelledEvent

**En tant que** développeur
**Je veux** un événement lors de l'annulation
**Afin de** gérer les remboursements et notifications

**Critères d'acceptance:**
- [ ] Event contient raison d'annulation
- [ ] Handler pour notification client
- [ ] Tests unitaires passent

**Points:** 3

---

### US-031: Intégrer les Events dans les Aggregates

**En tant que** développeur
**Je veux** que les Aggregates enregistrent leurs events
**Afin de** les dispatcher après persistance

**Critères d'acceptance:**
- [ ] Reservation utilise `RecordsDomainEvents`
- [ ] Events dispatchés dans les Use Cases
- [ ] Pattern `pullDomainEvents()` implémenté
- [ ] Tests d'intégration passent

**Points:** 5

---

## Total Points: 17

---

## Definition of Done

- [ ] Events dans `src/Domain/{BC}/Event/`
- [ ] Events `final readonly` avec `occurredOn`
- [ ] Handlers dans `src/Application/{BC}/EventHandler/`
- [ ] Messenger configuré pour dispatch async
- [ ] `make phpstan` passe
- [ ] `make test-unit` passe
- [ ] Couverture tests > 80%

---

## Dépendances

- Sprint 1-5 (structure, VOs, Repos, Use Cases, Domain Services)

---

## Risques

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Event Sourcing prématuré | Fort | Rester simple (events pour side-effects) |
| Events non persistés | Moyen | Outbox pattern si nécessaire |

