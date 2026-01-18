# Sprint 1 - Fondations : Structure Clean Architecture

> **Durée:** 2 semaines
> **Priorité:** CRITIQUE
> **Phase:** 1/4 - Fondations

---

## Sprint Goal

Mettre en place la structure de dossiers Clean Architecture et migrer les entités existantes vers le Domain layer sans annotations Doctrine.

---

## Contexte

Score actuel architecture: **6/25** - Refactoring majeur nécessaire.

Ce sprint pose les fondations pour toute la migration vers Clean Architecture + DDD.

---

## User Stories

### US-001: Créer la structure Domain Layer

**En tant que** développeur
**Je veux** une structure de dossiers Domain conforme à DDD
**Afin de** isoler la logique métier des détails techniques

**Critères d'acceptance:**
- [x] Dossier `src/Domain/` créé
- [x] Sous-dossiers par Bounded Context (Client, Reservation, etc.)
- [x] Chaque BC contient: Entity/, ValueObject/, Repository/, Event/, Exception/
- [x] Dossier `src/Domain/Shared/` pour les VOs partagés

**Points:** 3 ✅

---

### US-002: Créer la structure Application Layer

**En tant que** développeur
**Je veux** une structure Application layer pour les Use Cases
**Afin de** orchestrer la logique métier

**Critères d'acceptance:**
- [x] Dossier `src/Application/` créé
- [x] Sous-dossiers par BC
- [x] Structure UseCase/Command/Query par fonctionnalité

**Points:** 2 ✅

---

### US-003: Créer la structure Infrastructure Layer

**En tant que** développeur
**Je veux** une structure Infrastructure pour les implémentations techniques
**Afin de** séparer les détails d'implémentation du domaine

**Critères d'acceptance:**
- [x] Dossier `src/Infrastructure/` créé
- [x] Sous-dossiers: Persistence/Doctrine/, Notification/, Cache/
- [x] Mapping XML Doctrine dans Infrastructure/Persistence/Doctrine/Mapping/

**Points:** 2 ✅

---

### US-004: Créer la structure Presentation Layer

**En tant que** développeur
**Je veux** une structure Presentation pour les controllers
**Afin de** séparer l'interface utilisateur du métier

**Critères d'acceptance:**
- [x] Dossier `src/Presentation/` créé
- [x] Sous-dossiers: Controller/Web/, Controller/Api/, Controller/Admin/
- [x] Form/, Twig/, Command/ (CLI)

**Points:** 2 ✅

---

### US-005: Migrer les entités vers Domain (sans annotations)

**En tant que** développeur
**Je veux** des entités Domain pures sans Doctrine
**Afin de** respecter l'isolation du domaine

**Critères d'acceptance:**
- [x] Entités déplacées vers `src/Domain/{BC}/Entity/`
- [x] Annotations Doctrine retirées des entités
- [x] Entités déclarées `final class`
- [x] Constructeurs privés + factory methods statiques

**Points:** 5 ✅

---

### US-006: Créer les mappings XML Doctrine

**En tant que** développeur
**Je veux** des mappings XML séparés des entités
**Afin de** garder le Domain indépendant de Doctrine

**Critères d'acceptance:**
- [x] Fichiers .orm.xml créés dans Infrastructure/Persistence/Doctrine/Mapping/
- [x] Configuration Doctrine pour lire les mappings XML
- [x] Tests de persistance passent

**Points:** 5 ✅

---

## Total Points: 19

---

## Definition of Done

- [x] Structure de dossiers créée
- [x] Entités migrées sans annotations Doctrine
- [x] Mappings XML fonctionnels
- [x] `make phpstan` passe
- [x] `make test` passe
- [x] Documentation mise à jour

---

## Dépendances

- Aucune (premier sprint)

---

## Risques

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Régression tests | Fort | Exécuter tests après chaque migration |
| Mapping XML incorrect | Moyen | Valider avec `doctrine:schema:validate` |
