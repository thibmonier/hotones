# Sprint 2 - Fondations : Value Objects

> **Durée:** 2 semaines
> **Priorité:** CRITIQUE
> **Phase:** 1/4 - Fondations

---

## Sprint Goal

Créer les Value Objects fondamentaux (Email, Money, IDs typés) avec validation dans le constructeur et immutabilité.

---

## Contexte

Suite du Sprint 1 (structure créée). Les Value Objects sont la base de la type safety et de l'encapsulation des règles métier.

---

## User Stories

### US-007: Créer les Value Objects d'identité

**En tant que** développeur
**Je veux** des IDs typés pour chaque entité
**Afin de** garantir la type safety et éviter les erreurs d'ID

**Critères d'acceptance:**
- [ ] `ClientId` créé avec validation UUID
- [ ] `ReservationId` créé avec validation UUID
- [ ] `SejourId` créé avec validation UUID
- [ ] `ParticipantId` créé avec validation UUID
- [ ] Factory method `generate()` et `fromString()`
- [ ] Méthode `equals()` pour comparaison

**Points:** 3

---

### US-008: Créer le Value Object Email

**En tant que** développeur
**Je veux** un Value Object Email avec validation
**Afin de** garantir que les emails sont toujours valides

**Critères d'acceptance:**
- [ ] Classe `final readonly`
- [ ] Validation format email dans constructeur
- [ ] Méthode `getValue()` et `__toString()`
- [ ] Méthode `equals()` pour comparaison
- [ ] Tests unitaires couvrant cas nominaux et erreurs

**Points:** 2

---

### US-009: Créer le Value Object Money

**En tant que** développeur
**Je veux** un Value Object Money pour les montants
**Afin de** éviter les erreurs de calcul avec les floats

**Critères d'acceptance:**
- [ ] Stockage en centimes (int)
- [ ] Méthodes `add()`, `subtract()`, `multiply()`
- [ ] Validation montant positif
- [ ] Gestion devise (EUR par défaut)
- [ ] Tests unitaires pour tous les calculs

**Points:** 3

---

### US-010: Créer le Value Object PhoneNumber

**En tant que** développeur
**Je veux** un Value Object PhoneNumber
**Afin de** valider et normaliser les numéros de téléphone

**Critères d'acceptance:**
- [ ] Validation format E.164
- [ ] Normalisation (suppression espaces, tirets)
- [ ] Tests pour différents formats d'entrée

**Points:** 2

---

### US-011: Créer le Value Object PostalAddress

**En tant que** développeur
**Je veux** un Value Object PostalAddress
**Afin de** encapsuler les adresses postales

**Critères d'acceptance:**
- [ ] Composants: rue, code postal, ville, pays
- [ ] Validation code postal par pays
- [ ] Classe immutable

**Points:** 3

---

### US-012: Créer les Doctrine Custom Types

**En tant que** développeur
**Je veux** des types Doctrine personnalisés pour les VOs
**Afin de** persister les Value Objects en base

**Critères d'acceptance:**
- [ ] `EmailType` créé et enregistré
- [ ] `MoneyType` créé et enregistré
- [ ] Types d'ID créés et enregistrés
- [ ] Configuration `doctrine.yaml` mise à jour
- [ ] Tests de persistance passent

**Points:** 5

---

## Total Points: 18

---

## Definition of Done

- [ ] Tous les VOs créés dans `src/Domain/Shared/ValueObject/`
- [ ] Tous les VOs sont `final readonly`
- [ ] Validation dans les constructeurs
- [ ] Méthodes `equals()` implémentées
- [ ] Types Doctrine créés dans Infrastructure
- [ ] `make phpstan` passe
- [ ] `make test-unit` passe
- [ ] Couverture tests > 80%

---

## Dépendances

- Sprint 1 (structure des dossiers)

---

## Risques

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Migration données existantes | Moyen | Scripts de migration |
| Incompatibilité Doctrine | Moyen | Tests d'intégration |
