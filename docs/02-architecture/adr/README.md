# Architecture Decision Records (ADR)

Suivez le format [Michael Nygard](https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions) : Statut, Contexte, Options, Décision, Conséquences.

## Index

| # | Titre | Statut | Date |
|---|---|---|---|
| 0001 | _placeholder_ — premier ADR à venir | — | — |
| [0002](0002-mago-vs-php-cs-fixer-alignment.md) | Conflit alignement Mago vs PHP-CS-Fixer | Accepté | 2026-05-01 |
| [0003](0003-test-legacy-tolerance-vacation-csrf-session-boundary.md) | Tolérance test legacy permanente : 3 fichiers Vacation `skip-pre-push` (CSRF/session boundary) | Accepté | 2026-05-05 |

## Comment ajouter un ADR

1. Copier un ADR existant dans `docs/02-architecture/adr/NNNN-titre-en-kebab.md`.
2. Numéroter séquentiellement (`0003`, `0004`, ...).
3. Référencer l'ADR depuis le code/PR concerné (`// ADR-0002: ...`).
4. Mettre à jour cette index dans la même PR.

## Statut

- **Proposé** — soumis à l'équipe pour discussion
- **Accepté** — validé, en vigueur
- **Déprécié** — encore appliqué mais à remplacer
- **Remplacé par ADR-NNNN** — historique
