# ⏱️ Temps, Planification et Congés

## Timesheet (Suivi du temps)
- Date et durée en heures (ex : 7.5h)
- Lien Contributor ↔ Project
- Notes optionnelles

## Planning (Planification future)
- Écran Planning: timeline par contributeur (drag & drop, édition in-place)
  - URL: `/planning` (menu Planification → Planning)
  - Accès: `ROLE_CHEF_PROJET` et au-dessus
  - Colonnes: jours consécutifs (scroll horizontal), en-tête collant; lignes: contributeurs (colonne gauche figée)
  - Blocs: une planification par période (couleur selon statut planned/confirmed/cancelled), taille proportionnelle au nombre de jours
  - Actions: déplacer un bloc (drag&drop) pour changer la date de début; clic pour éditer début/fin, heures/jour, statut, notes via modal
  - Sécurité: endpoints protégés par CSRF et rôles; mise à jour en AJAX
- Positionnement de tâches/temps futurs (jours/semaines/mois), lien Contributeur ↔ Project, notes optionnelles

## Congés (Vacation)
- Dates, durée, type (congés payés, repos compensateur, absence exceptionnelle, arrêt maladie)
- Notes, statut
