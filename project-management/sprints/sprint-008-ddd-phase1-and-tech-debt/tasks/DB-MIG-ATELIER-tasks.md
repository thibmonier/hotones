# DB-MIG-ATELIER — Tasks

> Migrations DB suite atelier business sprint-007 (2 pts). 4 tasks / ~6h.

## Story rappel

Atelier business sprint-007 (Q1-Q13) requiert plusieurs nouveaux champs/entities BDD. Story livre les migrations Doctrine.

## Champs/Entities à ajouter

1. **Order.winProbability** (int 0-100, nullable) — Q5 atelier
2. **CompanySettings.aiKeysOpenAI**, **aiKeysAnthropic**, etc. (text encrypted, nullable) — Q8 atelier (fonctionnalité IA)
3. **AiUsageLog** (entity nouvelle): tracker usage IA par tenant, tokens, cost — Q8 atelier
4. **FULLTEXT INDEX** sur Client.name + Project.title + Order.title — Q11 atelier (search rapide cross-BC)

## Tasks

| ID | Type | Description | Estim | Dépend | Status |
|---|---|---|---:|---|---|
| T-DBM-01 | [DB] | Modifier `App\Entity\Order` — ajouter champ `winProbability: ?int` avec `#[Assert\Range(min:0, max:100)]` | 1h | - | 🔲 |
| T-DBM-02 | [DB] | Modifier `App\Entity\CompanySettings` — ajouter 4 fields aiKeysOpenAI/Anthropic/Mistral/Google (text encrypted via Symfony AbstractEncryptedFieldsListener si dispo, sinon plain stockage avec ADR) | 2h | - | 🔲 |
| T-DBM-03 | [DB] | Créer `App\Entity\AiUsageLog` — fields: tenantId, model, promptTokens, completionTokens, costUsd, occurredAt + repository + minimal admin queries | 2h | - | 🔲 |
| T-DBM-04 | [DB] | Migration unique `Version20260507XXXXXX.php` couvrant 1+2+3 + FULLTEXT INDEX. Test up/down | 1h | T-DBM-01, T-DBM-02, T-DBM-03 | 🔲 |

## Acceptance Criteria

- [ ] 1 migration générée + testée up/down sur DB de dev
- [ ] Order.winProbability + validation 0-100
- [ ] CompanySettings.aiKeys* nullable (companies sans IA continuent fonctionner)
- [ ] AiUsageLog entity + repository + 1 fixture sample
- [ ] FULLTEXT INDEX MariaDB-compatible (`ENGINE=InnoDB` + index `FULLTEXT KEY ...`)
- [ ] Pas de régression suite Functional sur Order/CompanySettings tests

## Notes

- MariaDB 11.4 supporte FULLTEXT sur InnoDB
- Encryption via Symfony Security Encoder optionnelle Phase 1 (deférable Phase 2 si compliqué)
- AiUsageLog n'a pas besoin d'UI Phase 1, juste le storage

## Sortie

Branche: `feat/db-mig-atelier-business`. PR base main.
