# AGENTS.md — Guide d’usage pour agents (Warp, Claude, etc.)

Ce document définit comment les agents doivent opérer dans ce dépôt. Il est compatible avec les conventions WARP.md/agents.md.

## Point d’entrée et contexte
- WARP.md est l’index unique. Les détails sont dans `docs/` (overview, features, time-planning, architecture, repositories, entities, status, installation, assets, urls, database, test-account, profitability, ui, analytics, good-practices, specs).
- Les agents doivent d’abord lire WARP.md puis piocher dans `docs/` selon le sujet.

## Principes de communication
- Réponses concises et factuelles; éviter le superflu et les résumés non demandés.
- CommonMark strict (inline code et listes). Éviter les emojis sauf demande explicite.
- Si on demande le modèle: répondre « auto ».

## Outils et commandes
- Préférer les outils d’édition/lecture de fichiers dédiés au terminal à l’IO brute; éviter l’interactif.
- Versionning: ne jamais utiliser de pager; pour git, ajouter `--no-pager` si applicable; ne jamais commit sans demande explicite.
- Sécurité des secrets: récupérer en variable d’environnement, ne jamais afficher/écho un secret.
- Commandes potentiellement risquées (écriture, suppression, migrations): demander confirmation préalable; expliquer brièvement le but avant exécution.
- Garder le répertoire courant stable; utiliser des chemins absolus quand pertinent.

## Conventions fichiers et code
- Chemins: relatifs dans l’arborescence du projet, absolus pour l’extérieur.
- Blocs de code (MDX-like):
  - Réel: ```lang path=/abs/path/to/file.ext start=LINE
  - Hypothétique: ```lang path=null start=null
  - Identifiant de langage en minuscule.

## Qualité et vérifications
- Lire/chercher le code avant d’éditer; respecter les patterns existants.
- Après modifications: lancer lint/typecheck/tests si des scripts existent (demander la commande si inconnue).
- Éviter les duplications; factoriser la doc vers `docs/` quand pertinent.

## Autonomie et confidentialité
- Minimiser les actions sans supervision; demander validation pour opérations non triviales.
- Ne pas exfiltrer du contenu privé; référencer uniquement les sources locales/disponibles.

## Sections utiles
- Performance: `docs/performance.md`.
- KPIs/Analytics: voir `docs/analytics.md`.
- Architecture et bundles: `docs/architecture.md`.
- Entités/Modèle: `docs/entities.md`.
- Procédures d’installation et assets: `docs/installation.md`, `docs/assets.md`.

Dernière mise à jour: 2025-10-30
