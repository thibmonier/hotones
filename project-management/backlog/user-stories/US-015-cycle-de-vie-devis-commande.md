# US-015 — Cycle de vie devis/commande

> **BC**: ORD  |  **Source**: archived ORD.md (split 2026-05-11)

> INFERRED from `OrderStatus` enum + `OrderController`.

- **Implements**: FR-ORD-01
- **Persona**: P-002, P-005
- **Estimate**: 8 pts
- **MoSCoW**: Must

### Card
**As** chef de projet ou admin
**I want** faire évoluer un devis dans son cycle de vie (PENDING → WON/LOST/STANDBY/ABANDONED → SIGNED → COMPLETED)
**So that** la plateforme reflète l'état réel de la négociation et de la livraison.

### Acceptance Criteria
**Scenario nominal — devis gagné puis signé**
```
Given devis statut "a_signer" (PENDING)
When CP marque "gagne" (WON)
Then statut WON + QuoteStatusChangedEvent dispatché
And notification QUOTE_WON envoyée aux parties prenantes
```
```
Given devis WON
When client signe (SIGNED)
Then statut SIGNED, projet pouvant être amorcé
```
```
Given devis SIGNED + livraison validée
When CP/admin marque COMPLETED
Then statut final, calculs de rentabilité figés
```

**Scenario alternatif — perdu**
```
Given devis PENDING
When marqué LOST avec motif
Then statut LOST + notification QUOTE_LOST
```

**Scenario erreur — transition interdite**
```
Given devis COMPLETED
When tentative de retour en PENDING
Then refusé (state machine guard)
```

### Technical Notes
- Enum `App\Enum\OrderStatus` (PENDING/WON/SIGNED/LOST/COMPLETED/STANDBY/ABANDONED)
- Event `QuoteStatusChangedEvent` → notifications
- ⚠️ State machine formelle non détectée: à garder simple ou utiliser `symfony/workflow`

---

