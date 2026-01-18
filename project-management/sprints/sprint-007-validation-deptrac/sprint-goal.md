# Sprint 7 - Validation Architecture (Deptrac + CI/CD)

> **Durée:** 2 semaines
> **Priorité:** CRITIQUE
> **Phase:** 4/4 - Validation & Automatisation

---

## Sprint Goal

Valider l'architecture avec Deptrac et automatiser les contrôles qualité dans la CI/CD.

---

## Contexte

L'architecture Clean + DDD est en place. Il faut maintenant s'assurer qu'elle reste respectée dans le temps avec des outils de validation automatique.

---

## User Stories

### US-032: Configurer Deptrac pour Clean Architecture

**En tant que** développeur
**Je veux** Deptrac configuré pour valider les couches
**Afin de** détecter les violations de dépendances

**Critères d'acceptance:**
- [ ] `deptrac.yaml` créé avec layers Domain/Application/Infrastructure/Presentation
- [ ] Règles: Domain → rien, Application → Domain, etc.
- [ ] `make deptrac` passe sans violation
- [ ] Documentation des règles

**Points:** 5

---

### US-033: Ajouter PHPStan niveau max

**En tant que** développeur
**Je veux** PHPStan configuré au niveau maximum
**Afin de** garantir la qualité du typage

**Critères d'acceptance:**
- [ ] `phpstan.neon` configuré level max
- [ ] Extensions Symfony et Doctrine installées
- [ ] `make phpstan` passe sans erreur
- [ ] Baseline si nécessaire pour legacy

**Points:** 3

---

### US-034: Configurer la CI GitHub Actions

**En tant que** développeur
**Je veux** une pipeline CI complète
**Afin de** valider chaque PR automatiquement

**Critères d'acceptance:**
- [ ] Workflow `.github/workflows/ci.yml` créé
- [ ] Jobs: phpstan, cs-fixer, deptrac, tests
- [ ] Badge de statut dans README
- [ ] Merge bloqué si CI échoue

**Points:** 5

---

### US-035: Ajouter les tests de couverture

**En tant que** développeur
**Je veux** un rapport de couverture de code
**Afin de** garantir 80% minimum

**Critères d'acceptance:**
- [ ] PHPUnit configuré avec coverage
- [ ] Rapport HTML généré
- [ ] Seuil 80% minimum configuré
- [ ] Upload du rapport dans CI

**Points:** 3

---

### US-036: Documenter l'architecture

**En tant que** développeur
**Je veux** une documentation de l'architecture
**Afin de** faciliter l'onboarding

**Critères d'acceptance:**
- [ ] `docs/architecture.md` créé
- [ ] Diagramme Mermaid des couches
- [ ] Exemples de création d'entités/VOs
- [ ] Guide de contribution

**Points:** 2

---

## Total Points: 18

---

## Definition of Done

- [ ] `make deptrac` → 0 violation
- [ ] `make phpstan` → 0 erreur
- [ ] `make quality` → tout passe
- [ ] CI GitHub Actions fonctionnelle
- [ ] Couverture tests > 80%
- [ ] Documentation à jour

---

## Dépendances

- Sprint 1-6 (architecture complète implémentée)

---

## Risques

| Risque | Impact | Mitigation |
|--------|--------|------------|
| Violations existantes | Fort | Baseline + correction progressive |
| CI trop lente | Moyen | Cache des dépendances |

---

## Critères de Succès du Refactoring

À la fin de ce sprint, l'architecture doit atteindre:

| Critère | Score Initial | Objectif |
|---------|--------------|----------|
| Structure Clean Architecture | 0/5 | 5/5 |
| Value Objects | 0/5 | 5/5 |
| Repository Pattern | 1/5 | 5/5 |
| Use Cases CQRS | 0/5 | 4/5 |
| Domain Events | 0/5 | 4/5 |
| **TOTAL** | **6/25** | **23/25** |

