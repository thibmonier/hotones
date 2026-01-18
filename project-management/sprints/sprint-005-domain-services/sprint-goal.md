# Sprint 5 - Domain Services

> **Durée:** 2 semaines
> **Priorité:** HAUTE
> **Phase:** 3/4 - Domain Services

---

## Sprint Goal

Créer les Domain Services pour la logique métier qui n'appartient pas aux entités.

---

## Contexte

Les Use Cases orchestrent, mais la logique métier complexe (pricing, validation cross-aggregate) doit être dans des Domain Services.

---

## User Stories

### US-023: Créer ReservationPricingService

**En tant que** développeur
**Je veux** un service de calcul de prix
**Afin de** centraliser la logique de tarification

**Critères d'acceptance:**
- [ ] Service dans `Domain/Reservation/Service/`
- [ ] Calcul prix par participant (adulte/enfant/bébé)
- [ ] Interface `DiscountPolicyInterface`
- [ ] Tests unitaires couvrant tous les cas

**Points:** 5

---

### US-024: Implémenter les politiques de remise

**En tant que** développeur
**Je veux** des politiques de remise extensibles
**Afin de** appliquer différentes réductions

**Critères d'acceptance:**
- [ ] `FamilyDiscountPolicy` (famille nombreuse)
- [ ] `EarlyBookingDiscountPolicy` (réservation anticipée)
- [ ] Pattern Strategy appliqué
- [ ] Tests unitaires pour chaque politique

**Points:** 5

---

### US-025: Créer SejourAvailabilityService

**En tant que** développeur
**Je veux** un service de vérification des disponibilités
**Afin de** valider les réservations

**Critères d'acceptance:**
- [ ] Service dans `Domain/Catalog/Service/`
- [ ] Vérifie places disponibles
- [ ] Gère les conflits de dates
- [ ] Tests unitaires passent

**Points:** 3

---

### US-026: Créer ReservationValidatorService

**En tant que** développeur
**Je veux** un service de validation des réservations
**Afin de** centraliser les règles de validation

**Critères d'acceptance:**
- [ ] Validation participants (min 1, max 10)
- [ ] Validation montant positif
- [ ] Validation dates cohérentes
- [ ] Exceptions métier explicites

**Points:** 3

---

## Total Points: 16

---

## Definition of Done

- [ ] Services dans `src/Domain/{BC}/Service/`
- [ ] Interfaces pour injection de dépendances
- [ ] Aucune dépendance vers Infrastructure
- [ ] `make phpstan` passe
- [ ] `make deptrac` passe
- [ ] Tests unitaires > 90% couverture

---

## Dépendances

- Sprint 1-4 (structure, VOs, Repos, Use Cases)

---

## Risques

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Anemic Domain Model | Fort | Review DDD patterns |
| Over-engineering | Moyen | Appliquer YAGNI |
