# Module: Quotes & Orders

> **DRAFT** — stories `INFERRED` from codebase.
> Source: `project-management/prd.md` §5.3 (FR-ORD-01..FR-ORD-05)
> Generated: 2026-05-04

---

## US-015 — Cycle de vie devis/commande

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

## US-016 — Composer un devis structuré

> INFERRED from `OrderSection`, `OrderLine`, `OrderTask`.

- **Implements**: FR-ORD-02
- **Persona**: P-002, P-005
- **Estimate**: 8 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** composer un devis avec sections, lignes (jours × TJM) et tâches détaillées
**So that** le devis est lisible côté client et exploitable côté delivery.

### Acceptance Criteria
```
Given devis vide
When POST /orders/{id}/add-section
Then section créée
```
```
Given section
When add-line avec quantité × TJM
Then total recalculé (HT, TVA, TTC selon pays — cf. FR i18n)
```
```
Given section avec tâches
When génère le projet (au passage SIGNED)
Then ProjectTask créés à partir des OrderTask
```

### Technical Notes
- Routes existantes: `/add-line`, `/add-section`
- Calcul TVA dépend du pays (FR-CRM si VAT scope multi-pays — à valider)

---

## US-017 — Échéancier de paiement sur commande

> INFERRED from `OrderPaymentSchedule`.

- **Implements**: FR-ORD-03
- **Persona**: P-004, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Should

### Card
**As** comptabilité ou admin
**I want** définir un échéancier de paiement sur la commande (acompte 30%, jalons, solde)
**So that** la facturation et le suivi de trésorerie sont alignés sur les conditions négociées.

### Acceptance Criteria
```
Given commande SIGNED de 10 000 €
When je définis 30% à signature, 40% mid-projet, 30% livraison
Then 3 OrderPaymentSchedule créés avec dates prévisionnelles
And chacun déclenche une facture le moment venu (FR-INV-02)
```
```
Given somme des % ≠ 100%
When sauvegarde
Then refusée (validation)
```

---

## US-018 — Notifier les changements de statut devis

> INFERRED from `QuoteStatusChangedEvent` + `NotificationType::QUOTE_TO_SIGN/WON/LOST`.

- **Implements**: FR-ORD-04
- **Persona**: P-002, P-003, P-005
- **Estimate**: 3 pts
- **MoSCoW**: Must

### Card
**As** chef de projet, manager ou admin
**I want** être notifié quand un devis change de statut
**So that** je réagis vite (relancer un client, lancer un projet, archiver un perdu).

### Acceptance Criteria
```
Given devis change de PENDING à WON
When event QuoteStatusChangedEvent dispatché
Then notification QUOTE_WON créée pour CP + manager
And canaux configurés (in-app, email selon NotificationPreference)
```
```
Given event consommé en async (messenger Redis)
Then aucune latence sur la requête HTTP qui a déclenché le changement
```

### Technical Notes
- Event subscriber dans `Infrastructure/Notification`
- `NotificationChannel` enum
- Cf. FR-NTF-03

---

## US-019 — Export PDF d'un devis

> INFERRED from `dompdf/dompdf` dependency + `Service/Order/*`.

- **Implements**: FR-ORD-05
- **Persona**: P-002, P-005
- **Estimate**: 5 pts
- **MoSCoW**: Must

### Card
**As** chef de projet
**I want** générer un PDF du devis pour l'envoyer au client
**So that** je formalise l'offre commerciale.

### Acceptance Criteria
```
Given devis composé (sections + lignes)
When GET /orders/{id}/pdf
Then PDF retourné avec en-tête société, lignes, totaux, conditions, signature
```
```
Given PDF lourd (>50 pages)
When génération
Then offloaded en async (messenger), URL téléchargement notifiée
```

### Technical Notes
- Symfony AssetMapper / Webpack pour assets PDF
- Branding société depuis `CompanySettings`

---

## Module summary

| ID | Title | FR | Pts | MoSCoW |
|----|-------|----|----|--------|
| US-015 | Cycle de vie devis | FR-ORD-01 | 8 | Must |
| US-016 | Composer devis structuré | FR-ORD-02 | 8 | Must |
| US-017 | Échéancier paiement | FR-ORD-03 | 5 | Should |
| US-018 | Notifier changement statut | FR-ORD-04 | 3 | Must |
| US-019 | Export PDF devis | FR-ORD-05 | 5 | Must |
| **Total** | | | **29** | |
