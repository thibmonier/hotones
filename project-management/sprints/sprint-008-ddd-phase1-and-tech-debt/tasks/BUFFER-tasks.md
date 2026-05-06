# Buffer Stories — DDD-PHASE1-INVOICE + TEST-COVERAGE-002

> Activation conditionnelle si capacité disponible après 8 stories ferme.

---

## DDD-PHASE1-INVOICE (3 pts) — 7 tasks / ~14h

### Files attendus

```
src/Domain/Invoice/
├── Entity/Invoice.php
├── Entity/InvoiceLine.php
├── Event/InvoiceCancelledEvent.php
├── Event/InvoiceCreatedEvent.php
├── Event/InvoiceIssuedEvent.php
├── Event/InvoicePaidEvent.php
├── Exception/InvalidInvoiceException.php
├── Exception/InvoiceNotFoundException.php
├── Repository/InvoiceRepositoryInterface.php
├── ValueObject/InvoiceId.php
├── ValueObject/InvoiceLineId.php
├── ValueObject/InvoiceNumber.php
├── ValueObject/InvoiceStatus.php
└── ValueObject/TaxRate.php
```

### Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-DDP1I-01 | [DDD] | Cherry-pick 14 fichiers Domain Invoice depuis tag proto | 3h | DDD-PHASE0-002 | ⏸️ |
| T-DDP1I-02 | [DDD] | Cherry-pick Mapping XML + custom types Doctrine + DBAL 4 adapt | 2h | T-DDP1I-01 | ⏸️ |
| T-DDP1I-03 | [DDD] | Verify Invoice DDD invariants — status transitions (CREATED→ISSUED→PAID/CANCELLED) | 2h | T-DDP1I-01 | ⏸️ |
| T-DDP1I-04 | [DDD] | TaxRate VO interop — vérifier compat avec FR-OPS-08 atelier | 1h | T-DDP1I-01 | ⏸️ |
| T-DDP1I-05 | [TEST] | Tests Unit Invoice + InvoiceLine + 5 VOs + 4 Events (~25 tests) | 4h | T-DDP1I-03 | ⏸️ |
| T-DDP1I-06 | [DOC] | ADR-0008 — Invoice DDD + coexistence Invoice flat | 1h | T-DDP1I-05 | ⏸️ |
| T-DDP1I-07 | [REV] | Self-review + Deptrac + suite | 1h | T-DDP1I-05, T-DDP1I-06 | ⏸️ |

---

## TEST-COVERAGE-002 (2-3 pts) — 4 tasks / ~8h

### Story rappel

Sprint-006 a posé l'escalator coverage 25→30→35→40→45% sur 5 sprints. Sprint-007 ne l'a pas explicitement avancé (focus sécurité). Sprint-008 cible step 25→30%.

### Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-TC2-01 | [TEST] | Audit coverage actuel: `vendor/bin/phpunit --coverage-text`. Identifier top 5 services sous-couverts (probablement nouveaux fichiers Voter + Multitenant) | 1h | - | ⏸️ |
| T-TC2-02 | [TEST] | Lot 1: push tests sur 2 services prioritaires (cible +5% coverage chacun) | 3h | T-TC2-01 | ⏸️ |
| T-TC2-03 | [TEST] | Lot 2: edge cases / error paths sur services existants 80-90% (push to 95%+) | 3h | T-TC2-01 | ⏸️ |
| T-TC2-04 | [DOC] | Update tracker coverage doc (sprint-006 escalator) avec snapshot S-008 | 1h | T-TC2-02, T-TC2-03 | ⏸️ |

---

## Activation buffer

Critère décisionnel: si à mi-sprint (après ~10h cumulé), 4+ stories ferme sont **Done** ou **Review**, activer 1 buffer story (DDD-PHASE1-INVOICE prio si DDD-PHASE0-002 mergé, sinon TEST-COVERAGE-002).

Si 6+ stories ferme **Done**, activer les 2 buffer (mais ne pas dépasser 22 pts capacité).
