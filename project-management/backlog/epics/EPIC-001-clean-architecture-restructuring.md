# EPIC-001: Restructuration Clean Architecture

**Statut**: 📋 Backlog
**Priorité**: 🔴 CRITIQUE
**Effort Estimé**: 3-4 sprints (Phases 1-2)
**Business Value**: 🔴 TRÈS ÉLEVÉ
**Risque Technique**: 🟠 ÉLEVÉ

---

## Vue d'ensemble

Transformer l'architecture actuelle de type Symfony traditionnel vers une **Clean Architecture** avec séparation stricte des couches Domain/Application/Infrastructure/Presentation. Cette restructuration constitue la **fondation** de toute la refonte architecturale.

### Problème adressé

**Audit Report - Problem #1**: Absence d'architecture en couches
- **Score actuel**: Structure des Couches 0/5 ❌
- **Impact**: Maintenance difficile, tests complexes, évolution risquée
- **Fichiers concernés**: Structure complète du projet `src/`

### Solution proposée

Mise en place de la structure Clean Architecture avec:

```
src/
├── Domain/              # Coeur métier (AUCUNE dépendance externe)
│   ├── Client/
│   │   ├── Entity/Client.php
│   │   ├── ValueObject/ClientId.php
│   │   ├── Repository/ClientRepositoryInterface.php
│   │   └── Event/ClientCreatedEvent.php
│   └── Shared/
│       └── ValueObject/
│           ├── Email.php
│           └── PhoneNumber.php
├── Application/         # Use Cases
│   ├── Client/
│   │   ├── CreateClient/
│   │   │   ├── CreateClientCommand.php
│   │   │   └── CreateClientHandler.php
│   │   └── Query/
│   │       └── GetClientQuery.php
├── Infrastructure/      # Détails techniques
│   └── Persistence/
│       └── Doctrine/
│           ├── Repository/DoctrineClientRepository.php
│           └── Mapping/Client.orm.xml
└── Presentation/        # UI (Controllers, Forms, CLI)
    └── Controller/ClientController.php
```

---

## Objectifs métier

### Bénéfices attendus

1. **Réduction du temps de développement de 30%**
   - Logique métier isolée et testable unitairement
   - Pas de dépendances framework dans Domain
   - Tests rapides (< 100ms par test unitaire)

2. **Facilitation onboarding développeurs**
   - Architecture claire et documentée
   - Séparation des responsabilités évidente
   - Réduction temps formation: 3 jours → 1 jour

3. **Amélioration maintenabilité**
   - Changement framework facilité (si nécessaire)
   - Évolution code sans effet de bord
   - Dette technique réduite

4. **Réduction bugs production de 40%**
   - Couverture tests ≥ 80%
   - Mutation score ≥ 80%
   - Validation architecture automatisée (Deptrac)

---

## Exigences liées

- **REQ-001**: Séparation des Couches Architecturales
- **REQ-002**: Entités Domain Pures (sans Doctrine)
- **REQ-004**: Repository Interfaces (partie Domain)

---

## User Stories associées

### Phase 1: Fondations (Sprints 1-2)

- **US-001**: Créer la structure de répertoires Domain/Application/Infrastructure/Presentation
- **US-002**: Extraire l'entité Client vers Domain pur (supprimer annotations Doctrine)
- **US-003**: Créer le mapping Doctrine XML pour Client dans Infrastructure
- **US-004**: Extraire l'entité User vers Domain pur
- **US-005**: Créer le mapping Doctrine XML pour User dans Infrastructure
- **US-006**: Extraire l'entité Order vers Domain pur
- **US-007**: Créer le mapping Doctrine XML pour Order dans Infrastructure

### Phase 2: Abstractions (Sprints 3-4)

- **US-008**: Déplacer les Controllers vers Presentation layer
- **US-009**: Créer la structure Application avec Command/Query/Handler
- **US-020**: Créer ClientRepositoryInterface dans Domain (lien EPIC-003)
- **US-021**: Implémenter DoctrineClientRepository dans Infrastructure (lien EPIC-003)

---

## Critères d'acceptation (EPIC)

### Architecture

- [ ] Répertoires `src/Domain/`, `src/Application/`, `src/Infrastructure/`, `src/Presentation/` créés
- [ ] Entités Domain **sans annotations Doctrine** (pures)
- [ ] Mappings Doctrine XML/YAML dans `Infrastructure/Persistence/Doctrine/Mapping/`
- [ ] Controllers dans `Presentation/Controller/`
- [ ] Use Cases dans `Application/[Context]/UseCase/`

### Validation Deptrac

```yaml
# deptrac.yaml
ruleset:
    Domain: []  # ✅ Domain ne dépend de RIEN
    Application:
        - Domain  # ✅ Application dépend uniquement de Domain
    Infrastructure:
        - Domain
        - Application  # ✅ Infrastructure dépend de Domain et Application
    Presentation:
        - Application
        - Infrastructure
        - Domain  # Pour les VOs dans les DTOs
```

- [ ] `make deptrac` passe sans violation
- [ ] Score "Structure des Couches": 0/5 → 5/5

### Tests

- [ ] Tests unitaires Domain **sans dépendances** (pas de Symfony, pas de Doctrine)
- [ ] Tests d'intégration Infrastructure avec base de données
- [ ] Couverture code ≥ 80% sur Domain
- [ ] Tous les tests passent: `make test`

### Documentation

- [ ] ADR (Architecture Decision Record) créé pour justifier Clean Architecture
- [ ] Diagrammes architecture mis à jour (Mermaid)
- [ ] Guide migration pour futurs modules documenté

---

## Métriques de succès

| Métrique | Avant | Cible | Validation |
|----------|-------|-------|------------|
| **Structure Couches** | 0/5 ❌ | 5/5 ✅ | Audit architectural |
| **Violations Deptrac** | N/A | 0 | `make deptrac` |
| **Entités pures Domain** | 0% | 100% | Aucune annotation Doctrine dans Domain |
| **Couverture Domain** | Non mesuré | ≥ 80% | `make test-coverage` |
| **Tests unitaires rapides** | N/A | < 100ms/test | PHPUnit metrics |

---

## Dépendances

### Bloquantes (doivent être faites avant)

- Aucune (première phase)

### Bloquées par cet EPIC

- **EPIC-002**: Value Objects Implementation (peut être fait en parallèle Phase 1)
- **EPIC-003**: Repository Abstraction (nécessite structure Domain créée)
- **EPIC-004**: Domain Services and Events (nécessite entités Domain pures)

---

## Risques et mitigations

### Risque 1: Régression fonctionnelle
- **Probabilité**: Moyenne
- **Impact**: Élevé
- **Mitigation**:
  - Tests fonctionnels existants maintenus
  - Migration module par module (Strangler Fig Pattern)
  - Rollback possible via Git

### Risque 2: Complexité mapping Doctrine XML
- **Probabilité**: Moyenne
- **Impact**: Moyen
- **Mitigation**:
  - Commencer par entité simple (Client)
  - Documentation Doctrine XML détaillée
  - Pair programming sur premiers mappings

### Risque 3: Confusion développeurs
- **Probabilité**: Élevée
- **Impact**: Moyen
- **Mitigation**:
  - Formation Clean Architecture (1 session)
  - Documentation ADR + exemples concrets
  - Code reviews strictes phases initiales

---

## Approche d'implémentation

### Stratégie: Strangler Fig Pattern

1. **Créer nouvelle structure** à côté de l'ancienne
2. **Migrer module par module** (Client → User → Order)
3. **Maintenir ancienne structure** fonctionnelle pendant migration
4. **Supprimer ancien code** une fois migration validée

### Ordre de migration recommandé

1. **Client** (simple, peu de dépendances) ✅ Prioritaire
2. **User** (authentication, relations modérées)
3. **Order** (plus complexe, nombreuses relations)
4. **Autres entités** (suivant même pattern)

### Validation continue

- À chaque entité migrée:
  - [ ] Tests unitaires passent
  - [ ] Tests intégration passent
  - [ ] Deptrac valide sans violation
  - [ ] Fonctionnalités métier intactes

---

## Références

### Documentation interne

- `.claude/rules/02-architecture-clean-ddd.md` - Architecture obligatoire
- `.claude/rules/04-solid-principles.md` - Principes SOLID
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` - Audit source (lignes 29-42, 222-274)

### Checklist Phase 1 (Audit Report)

**Semaine 1-2** (lignes 380-384):
- [x] Créer la structure Domain/Application/Infrastructure/Presentation
- [ ] Extraire les entités Domain (sans annotations Doctrine)
- [ ] Créer les mappings Doctrine XML dans Infrastructure
- [ ] Créer les premiers Value Objects (Email, Money, IDs) - **EPIC-002**

### Ressources externes

- [Clean Architecture - Robert C. Martin](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)
- [Hexagonal Architecture - Alistair Cockburn](https://alistair.cockburn.us/hexagonal-architecture/)
- [Doctrine XML Mapping](https://www.doctrine-project.org/projects/doctrine-orm/en/latest/reference/xml-mapping.html)

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création EPIC | Claude (via workflow-plan) |
| 2026-01-13 | Validation priorité CRITIQUE | Architecture audit score 6/25 |

---

## Notes

- **Prerequis**: Lecture obligatoire de `.claude/rules/02-architecture-clean-ddd.md` avant implémentation
- **TDD obligatoire**: Tous les tests doivent être écrits AVANT implémentation (cycle RED → GREEN → REFACTOR)
- **Definition of Done**: Voir `/Users/tmonier/Projects/hotones/project-management/prd.md` section "Définition de Done"
