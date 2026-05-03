# Pull Request Template

## Description

<!-- Décrivez brièvement les changements apportés -->

## Type de changement

- [ ] 🐛 Bug fix (changement non-breaking qui corrige un problème)
- [ ] ✨ Nouvelle fonctionnalité (changement non-breaking qui ajoute une fonctionnalité)
- [ ] 💥 Breaking change (fix ou feature qui causerait un dysfonctionnement des fonctionnalités existantes)
- [ ] 📝 Documentation (mise à jour de la documentation uniquement)
- [ ] 🔧 Configuration (changements de configuration, CI/CD)
- [ ] ♻️ Refactoring (changement de code qui n'ajoute pas de fonctionnalité et ne corrige pas de bug)
- [ ] 🎨 Style (formatage, espaces, etc. - pas de changement de code)
- [ ] ⚡ Performance (changement qui améliore les performances)
- [ ] 🧪 Tests (ajout ou modification de tests)

## Tickets liés

<!-- Liens vers les issues/tickets associés -->
- Closes #
- Relates to #

## Changements effectués

<!-- Liste des changements principaux -->
-
-
-

## Screenshots / Vidéos

<!-- Si applicable, ajoutez des captures d'écran ou vidéos -->

| Avant | Après |
|-------|-------|
|       |       |

## Checklist

### Qualité du code
- [ ] Mon code suit les conventions du projet
- [ ] J'ai effectué une auto-revue de mon code
- [ ] J'ai commenté mon code, particulièrement dans les parties difficiles à comprendre
- [ ] Mes changements ne génèrent pas de nouveaux warnings

### Tests
- [ ] J'ai ajouté des tests qui prouvent que mon fix est efficace ou que ma feature fonctionne
- [ ] Les tests unitaires existants passent localement
- [ ] Les tests d'intégration passent

### Documentation
- [ ] J'ai mis à jour la documentation si nécessaire
- [ ] J'ai mis à jour le CHANGELOG si applicable

### Sécurité
- [ ] J'ai vérifié qu'il n'y a pas de vulnérabilités de sécurité introduites
- [ ] Aucune donnée sensible n'est exposée dans les logs ou le code

### Performance
- [ ] J'ai considéré l'impact sur les performances
- [ ] Pas de requêtes N+1 introduites
- [ ] Pas de memory leaks introduits

### Workflow gated (OPS-015 — si applicable)

<!--
Cocher uniquement si la PR ajoute / modifie un workflow GitHub Actions
gated par `vars.X_ENABLED` ou nécessitant un nouveau `secrets.Y`.
Sprint-005 a vu plusieurs PRs mergées avec workflows skipped en silence
faute de provisioning.
-->

- [ ] N/A — la PR n'ajoute pas de workflow gated.
- [ ] Variables / secrets provisionnés sur le repo **AVANT** merge (`Settings → Secrets and variables → Actions`)
- [ ] OU déficience documentée : le workflow restera dormant jusqu'au provisioning ops, et c'est acceptable parce que :

      <!-- expliquer pourquoi : ex. workflow non critique, secret payant, etc. -->

- [ ] Test manuel `workflow_dispatch` planifié post-merge (mentionner dans la todo)

### Quota PR (OPS-013)

- [ ] J'ai au plus 4 PRs en review active simultanément (cf. `CONTRIBUTING.md`).
- [ ] Si > 4 : cette PR est ouverte en `draft`, j'ai noté quelle PR de mon stack je vais undraft en premier.

## Comment tester

<!-- Instructions pour tester les changements -->
1.
2.
3.

## Notes pour les reviewers

<!-- Informations importantes pour la review -->

## Checklist Reviewer

- [ ] Le code est lisible et bien structuré
- [ ] La logique métier est correcte
- [ ] Les edge cases sont gérés
- [ ] Les tests sont pertinents
- [ ] Pas de code dupliqué inutile
- [ ] Les messages d'erreur sont clairs
