# ADR-0009 — DDD Phase 3 controller migration pattern

**Status**: Accepted
**Date**: 2026-05-06
**Author**: Claude Opus 4.7 (1M context)
**Sprint**: sprint-010 — DDD-PHASE3-CONTROLLER-MIGRATION (4 pts)

---

## Context

Sprint-010 a complété EPIC-001 Phase 2 (3 BCs avec ACL + use cases pour Client/Project/Order).

Phase 3 démarre: migration progressive des controllers Symfony pour qu'ils utilisent les use cases DDD au lieu de manipuler directement les Entity flat.

**Problème**: les controllers existants (`ClientController`, `ProjectController`, `OrderController`, etc.) couplent fortement la logique métier (validation, transitions de statut, calculs) avec la couche présentation (form binding, redirects, flash messages).

**Goal Phase 3**: découpler en 4 strates:
1. Controller — HTTP request/response, form binding, flash, redirects
2. UseCase — orchestration métier
3. Repository (ACL) — persistance via flat
4. Domain — invariants + state machine

## Decision

**Pattern de migration progressive** (sans casser les routes existantes):

### Stratégie "augmenter, ne pas remplacer"

Pour chaque action controller candidate:

1. **Garder** la méthode legacy (mêmes route + nom + comportement)
2. **Ajouter** une nouvelle méthode utilisant le UC DDD avec une route distincte
3. **Tester** le nouveau path avec functional E2E
4. Une fois feature parity atteinte: déprécier la legacy, déprouver la DDD comme route principale (Phase 4)

### Exemple sprint-010 — ClientController

**Avant** (legacy seulement):
```php
#[Route('/new', name: 'client_new', methods: ['GET', 'POST'])]
public function new(Request $request, EntityManagerInterface $em): Response
{
    $client = new Client();
    // ... 30+ lines of inline logic
    $em->persist($client);
    $em->flush();
    return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
}
```

**Après** (legacy + DDD):
```php
#[Route('/new', ...)]  // ← INCHANGÉ
public function new(Request $request, EntityManagerInterface $em): Response { ... }

#[Route('/new-via-ddd', name: 'client_new_ddd', methods: ['GET', 'POST'])]
public function newViaDdd(Request $request, CreateClientUseCase $useCase): Response
{
    if ($request->isMethod('POST')) {
        try {
            $command = new CreateClientCommand(
                name: $request->request->get('name'),
                serviceLevel: $request->request->get('service_level', 'standard'),
                notes: $request->request->get('description'),
            );
            $clientId = $useCase->execute($command);
            $this->addFlash('success', 'Client créé avec succès via DDD use case');
            return $this->redirectToRoute('client_show', ['id' => $clientId->toLegacyInt()]);
        } catch (InvalidArgumentException $e) {
            $this->addFlash('danger', 'Validation: '.$e->getMessage());
        }
    }
    return $this->render('client/new.html.twig', ['client' => null]);
}
```

### Avantages

- **Zero risque pour la prod**: route legacy intacte
- **A/B testing possible**: utilisateurs admin peuvent essayer la route DDD
- **Test de pattern**: valide ACL + UC en conditions réelles
- **Simplification controller**: passe de 30+ lignes à ~10 lignes
- **Validation centralisée**: VOs domain rejettent les entrées invalides

### Limites

- Routes dédoublées temporairement (URL + route name distincts)
- Templates partagés (le UC ne couvre pas toutes les fonctionnalités legacy comme logo upload — celui-ci reste sur la route legacy)
- Domain events dispatched par UC mais pas par legacy → comportement asymmétrique pendant migration

## Roadmap Phase 3 → Phase 4

| Sprint | BC | Action |
|---|---|---|
| **010 (ICI)** | Client | 1ère migration: `/new-via-ddd` |
| 011 | Client | Migration `/edit-via-ddd` + delete |
| 011-012 | Project | Migrations create + edit + status transitions |
| 012-013 | Order | Migrations create quote + signature workflow |
| 013-014 | All | **Phase 4 démarre**: décommissionner les routes legacy une par une |

### Critères de promotion d'une route DDD vers main route

Une route `/X-via-ddd` peut remplacer `/X` (et hériter du nom) quand:
- ✅ Tous les tests functional E2E legacy passent sur la route DDD
- ✅ Toutes les fonctionnalités legacy couvertes (incluant edge cases comme logo upload)
- ✅ Code review valide
- ✅ Equipe confirme aucune régression UAT

## When NOT to use this pattern

- **Action sans logique métier** (export CSV, simple show GET) — garder Entity flat direct, pas de UC
- **Endpoints API Platform** — ces endpoints ont leur propre cycle (DTO, processors), Phase 5 les traitera
- **Bulk operations** (batch import) — Phase 4 candidate

## Dual-write concerns

Pendant Phase 3 (routes legacy + DDD coexistent), des incohérences sont possibles:
- Un client créé via legacy n'émet PAS de `ClientCreatedEvent` → audit log incomplet
- Un client créé via DDD émet l'event mais l'event consumer pourrait persister un side-effect que la route legacy ne déclenchait pas

**Mitigation**: pendant Phase 3:
- Ne pas faire dépendre des features critiques de `ClientCreatedEvent`
- Si feature critique → l'implémenter en Phase 4 quand legacy supprimée

## References

- **ADR-0008** Anti-Corruption Layer pattern (foundation)
- **PR #140** DoctrineDddClientRepository
- **PR #141** CreateClientUseCase
- **EPIC-001** Migration Clean Architecture + DDD

---

**Approved**: branche `feat/ddd-phase3-controller-migration-create-client`, PR #148.
