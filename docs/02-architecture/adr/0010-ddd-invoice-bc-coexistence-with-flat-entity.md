# ADR-0010 — DDD Invoice BC: coexistence avec Entity flat

**Status**: Accepted
**Date**: 2026-05-06
**Sprint**: sprint-011 — DDD-PHASE1-INVOICE (4 pts buffer activé)

---

## Context

Sprint-011 a activé le buffer DDD-PHASE1-INVOICE (déféré sprints 008-010). Cherry-pick du BC Invoice depuis le tag `proto/ddd-baseline-2026-01-19`.

Suit le pattern **Coexistence** établi par ADR-0005/0006/0007.

## Decision

**Coexistence transitoire** Entity flat (`App\Entity\Invoice`) ↔ DDD aggregate (`App\Domain\Invoice\Entity\Invoice`).

### Livraison Phase 1

- 14 fichiers Domain (Invoice + InvoiceLine + 4 Events + 2 Exceptions + Repo + 5 VOs)
- ValueObjects: `InvoiceId` (UUID), `InvoiceLineId` (UUID), `InvoiceNumber` (format `F[YYYY][MM][NNN]`), `InvoiceStatus` (5 cases enum), `TaxRate` (decimal validation)
- Events: `InvoiceCreatedEvent`, `InvoiceIssuedEvent`, `InvoicePaidEvent`, `InvoiceCancelledEvent`
- State machine `InvoiceStatus`: DRAFT → SENT → PAID / OVERDUE → PAID, ou CANCELLED

### Phase 2 (sprint-012+)

ACL adapter à livrer ultérieurement, suivant le pattern Client/Project/Order:
- `App\Infrastructure\Invoice\Persistence\Doctrine\DoctrineDddInvoiceRepository`
- `App\Infrastructure\Invoice\Translator\InvoiceFlatToDddTranslator`
- `App\Infrastructure\Invoice\Translator\InvoiceDddToFlatTranslator`
- `InvoiceId::fromLegacyInt()` pour le bridge int auto-increment

## Consequences

**Positives**:
- 4 BCs DDD additifs livrés (Client + Project + Order + Invoice)
- State machine InvoiceStatus apporte anti-corruption (transitions explicites)
- TaxRate VO valide les taux décimaux (avant: float libre côté flat)
- 8 tests Unit garantissent les invariants

**Négatives**:
- ACL Phase 2 reste à faire pour l'utilisation pratique (use cases)
- Divergence statuts (flat utilise les valeurs FR `brouillon/envoyee/payee/en_retard/annulee` — DDD utilise les mêmes valeurs en backing string)

## References

- **ADR-0005/0006/0007** Pattern coexistence (Client/Project/Order)
- **EPIC-001** Migration Clean Architecture + DDD
- **PR #155** (cette PR)

---

**Approved**: branche `feat/ddd-phase1-invoice`, PR #155.
