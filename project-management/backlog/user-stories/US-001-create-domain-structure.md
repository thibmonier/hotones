# US-001: Créer la structure de répertoires Domain/Application/Infrastructure/Presentation

**EPIC:** [EPIC-001](../epics/EPIC-001-clean-architecture-restructuring.md) - Restructuration Clean Architecture
**Priorité:** 🔴 CRITIQUE
**Points:** 3
**Sprint:** Sprint 1
**Statut:** ✅ Done

---

## Description

**En tant que** développeur
**Je veux** créer la structure de répertoires conforme à Clean Architecture
**Afin de** établir les fondations pour la migration architecturale

---

## Critères d'acceptation

### GIVEN: Le projet a une structure Symfony traditionnelle

**WHEN:** Je crée la nouvelle structure de répertoires

**THEN:**
- [x] Les répertoires `src/Domain/`, `src/Application/`, `src/Infrastructure/`, `src/Presentation/` existent
- [x] Chaque répertoire contient un fichier `.gitkeep` pour être versionné
- [x] Les sous-répertoires suivants sont créés:
  - `src/Domain/Client/`, `src/Domain/User/`, `src/Domain/Order/`, `src/Domain/Shared/`
  - `src/Application/Client/`, `src/Application/User/`, `src/Application/Order/`
  - `src/Infrastructure/Persistence/Doctrine/`, `src/Infrastructure/Notification/`
  - `src/Presentation/Controller/Web/`, `src/Presentation/Controller/Api/`

### GIVEN: La nouvelle structure existe

**WHEN:** J'exécute `ls -R src/`

**THEN:**
- [x] Toutes les couches architecturales sont visibles
- [x] La hiérarchie respecte le modèle Clean Architecture
- [x] Aucune erreur d'autoload Composer

---

## Tâches techniques

### [STRUCT] Créer répertoires Domain (1h)
- Créer `src/Domain/` avec sous-répertoires par Bounded Context
- Créer `src/Domain/Client/Entity/`, `src/Domain/Client/ValueObject/`, `src/Domain/Client/Repository/`
- Créer `src/Domain/User/Entity/`, etc.
- Créer `src/Domain/Shared/ValueObject/`, `src/Domain/Shared/Exception/`

### [STRUCT] Créer répertoires Application (1h)
- Créer `src/Application/` avec sous-répertoires par contexte
- Créer `src/Application/Client/UseCase/`, `src/Application/Client/Query/`
- Créer `src/Application/User/UseCase/`, etc.

### [STRUCT] Créer répertoires Infrastructure (0.5h)
- Créer `src/Infrastructure/Persistence/Doctrine/Repository/`
- Créer `src/Infrastructure/Persistence/Doctrine/Mapping/`
- Créer `src/Infrastructure/Persistence/Doctrine/Type/`
- Créer `src/Infrastructure/Notification/`

### [STRUCT] Créer répertoires Presentation (0.5h)
- Créer `src/Presentation/Controller/Web/`
- Créer `src/Presentation/Controller/Api/`
- Créer `src/Presentation/Form/`
- Créer `src/Presentation/Command/`

### [DOC] Documenter structure (0.5h)
- Mettre à jour README.md avec la nouvelle structure
- Créer `.claude/examples/clean-architecture-structure.md` si nécessaire

### [TEST] Valider autoload (0.5h)
- Exécuter `composer dump-autoload`
- Vérifier qu'aucune erreur PSR-4

---

## Définition de Done (DoD)

- [x] Toutes les couches Domain/Application/Infrastructure/Presentation créées
- [x] Sous-répertoires par Bounded Context présents
- [x] Fichiers `.gitkeep` ajoutés dans répertoires vides
- [x] `composer dump-autoload` passe sans erreur
- [x] Structure documentée dans README.md
- [x] Pas de violation PSR-4
- [x] Code review effectué par Tech Lead
- [x] Commit avec message: `feat(architecture): create Clean Architecture directory structure`

---

## Notes techniques

### Structure complète attendue

```
src/
├── Domain/
│   ├── Client/
│   │   ├── Entity/
│   │   ├── ValueObject/
│   │   ├── Repository/
│   │   ├── Service/
│   │   ├── Event/
│   │   └── Exception/
│   ├── User/
│   │   └── [même structure]
│   ├── Order/
│   │   └── [même structure]
│   └── Shared/
│       ├── ValueObject/
│       ├── Exception/
│       └── Interface/
├── Application/
│   ├── Client/
│   │   ├── UseCase/
│   │   ├── Query/
│   │   └── EventHandler/
│   └── [autres contextes]
├── Infrastructure/
│   ├── Persistence/
│   │   └── Doctrine/
│   │       ├── Repository/
│   │       ├── Mapping/
│   │       └── Type/
│   └── Notification/
└── Presentation/
    ├── Controller/
    │   ├── Web/
    │   ├── Api/
    │   └── Admin/
    ├── Form/
    └── Command/
```

### Bounded Contexts identifiés

- **Client:** Gestion clients/entreprises
- **User:** Authentification, utilisateurs
- **Order:** Commandes, facturation
- **Shared:** Value Objects partagés

---

## Dépendances

### Bloquantes
- Aucune (première User Story du projet)

### Bloque
- **US-002:** Extraction Client (nécessite `Domain/Client/Entity/`)
- **US-004:** Extraction User (nécessite `Domain/User/Entity/`)
- **US-010:** Value Objects (nécessite `Domain/Shared/ValueObject/`)

---

## Références

- `.claude/rules/02-architecture-clean-ddd.md` (lignes 53-258)
- `/Users/tmonier/Projects/hotones/var/architecture-audit-report.md` (lignes 230-259)
- **Livre:** *Clean Architecture* - Robert C. Martin, Chapitre 22

---

## Historique

| Date | Action | Auteur |
|------|--------|--------|
| 2026-01-13 | Création User Story | Claude (workflow-plan) |
| 2026-01-15 | Implémentation complétée - Sprint 001 | Claude (sprint-dev) |
