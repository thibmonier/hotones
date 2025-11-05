# 🎨 Interface utilisateur

## Pages principales à créer
- Dashboard avec KPIs
- Liste des projets avec rentabilité (CA, marge brute vendue, marge brute cible, rentabilité constatée, commercial, chef de projet)
- Détail projet (rentabilité, temps saisis, édition du projet)
- Saisie des temps (vue liste + agenda semaine ouvrée 8h→20h)
- Gestion des intervenants (employés/freelances avec prix d'achat)
- Rapports et analyses (performance globale en marge brute)
- Administration (users, périodes)
- Planning détaillé par intervenant (staffing prévisionnel)

## UX/UI
- Design responsive Bootstrap 5
- Thème "Skote" (admin dashboard)
- Formulaires avec validation
- Tableaux interactifs (sélecteur inline du statut de devis dans les listings)
- Graphiques (Chart.js ou similaire)

## 🧪 Tests E2E (UX de bout en bout)
- Scénarios: login, accès page d’accueil, listing projets, création de projet
- Bonnes pratiques: privilégier des sélecteurs stables (id/classes dédiées ou data-testid) pour fiabiliser les tests
- Outil: Panther (navigateur réel) — voir `docs/tests.md` pour configuration
