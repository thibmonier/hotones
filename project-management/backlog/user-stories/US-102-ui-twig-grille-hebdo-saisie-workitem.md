# US-102 — UI Twig grille hebdo saisie WorkItem

> **BC**: TIM  |  **Source**: archived TIM.md (split 2026-05-11)

- **Implements**: EPIC-003 Phase 3 — **Persona**: P-001 intervenant + P-002 manager — **Estimate**: 5 pts — **MoSCoW**: Must — **Sprint**: 021

### Card
**As** intervenant (P-001) ou manager (P-002)
**I want** une grille hebdomadaire (7 jours × N projets) avec drag-drop saisie heures + auto-save
**So that** je vois ma semaine d'un coup d'œil, équilibre projets visible, et chaque modification persistée immédiatement sans bouton submit.

### Acceptance Criteria

```
Given intervenant authentifié
When GET /timesheet/{week} (week ISO 8601 ex 2026-W19)
Then page Twig affiche grille 7 jours × projets actifs intervenant
And totaux par jour + par projet + total semaine affichés
```

```
Given intervenant saisit heures dans cellule (jour, projet)
When change heures (input change event)
Then auto-save POST UC RecordWorkItem (US-099)
And cellule met à jour visuellement (loader → ✓ saved)
And total jour + total projet + total semaine recalculés
```

```
Given saisie dépasse dailyMaxHours
When auto-save tenté avec userOverride = false
Then DailyHoursWarningException reçue côté UI
And popover/modal warning + checkbox "j'accepte override" affiché
```

```
Given user check "j'accepte override"
When auto-save retry avec userOverride = true
Then WorkItem créé avec audit log override
And cellule status devient "✓ saved (override)"
```

```
Given WorkItem date > 7 jours OU projet associé Invoice.status = billed/paid
And user n'est pas ROLE_ADMIN
When intervenant clique cellule
Then cellule disabled visuellement (grise + lock icon)
And tooltip explique raison (date > 7j OU projet facturé)
```

```
Given user ROLE_ADMIN
When clique cellule verrouillée
Then édition autorisée
And badge visuel "admin override" affiché
```

### Technical Notes
- ADR-0016 Q1.1 A grille hebdo + Q1.3 A step 0.25h + Q2.1 A auto-save
- ADR-0016 Q2.2 B+D édition lock + Q2.4 B warning override + Q2.3 A admin override
- ADR-0016 A-5
- Stimulus controller pour drag-drop + auto-save (Symfony UX Turbo / Live Components)
- Tests E2E Panther (au minimum scénario nominal saisie + warning override)
- Dépendance US-099 (consume UC) — ordre exécution US-099 → US-102 figé

---

