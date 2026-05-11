# US-082 — Messagerie asynchrone

> **BC**: OPS  |  **Source**: archived OPS.md (split 2026-05-11)

> INFERRED from `messenger.yaml`, MessageHandlers x7.

- **Implements**: FR-OPS-04 — **Persona**: système — **Estimate**: 5 pts — **MoSCoW**: Must

### Card
**As** plateforme HotOnes
**I want** offload des opérations longues sur des workers (Redis transport)
**So that** les requêtes HTTP restent rapides et la résilience monte.

### Acceptance Criteria
```
Given handler async
When message dispatché
Then traité par worker, retry sur échec, queue failed pour replay
```
```
Given queue saturée
Then back-pressure mesurée (alertes)
```

---

