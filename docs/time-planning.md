# ⏱️ Temps, Planification et Congés

## Timesheet (Suivi du temps)
- Date et durée en heures (ex : 7.5h)
- Lien Contributor ↔ Project (et optionnellement ProjectTask)
- Notes optionnelles
- Nouveau: Compteur de temps en direct (start/stop) avec imputation automatique sur la tâche
  - Démarrer depuis la page « Saisie des temps » via le bouton ▶️ d’une tâche
  - Un seul compteur peut être actif à la fois (démarrer un nouveau stoppe l’ancien et l’impute)
  - À l’arrêt, le temps est imputé immédiatement dans les temps passés du jour, avec un minimum de 0,125j (1h) crédité

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
