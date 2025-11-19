# ⏱️ Temps, Planification et Congés

## Timesheet (Suivi du temps)

### Saisie des temps
- Date et durée en heures (ex : 7.5h)
- Lien Contributor ↔ Project (et optionnellement ProjectTask)
- Notes optionnelles
- Interface de saisie hebdomadaire par projet/tâche
- Historique personnel et vue globale par mois

### Compteur de temps intégré
- Démarrer/arrêter depuis la page de saisie via le bouton ▶️ d'une tâche
- Un seul compteur peut être actif à la fois par contributeur
- Démarrer un nouveau compteur stoppe et impute automatiquement le précédent
- À l'arrêt, imputation automatique sur la tâche du jour avec minimum de 0,125j (1h)
- Timer visible en temps réel pendant l'exécution

## Planning Resource Timeline

### Vue d'ensemble
- **URL**: `/planning` (menu Delivery → Planning)
- **Accès**: `ROLE_CHEF_PROJET` et au-dessus
- **Technologie**: FullCalendar Scheduler (une ligne par contributeur)
- **Mode**: Timeline avec scroll horizontal, en-tête collant

### Fonctionnalités principales

#### Gestion des planifications
- **Création**: Drag & select pour créer une nouvelle planification
- **Modification des dates**: Drag & drop ou resize directement sur la timeline
- **Édition complète**: Modal avec dates, heures/jour, statut, notes
- **Division (split)**: Séparer une planification en plusieurs périodes non consécutives
- **Statuts**: planned (planifié), confirmed (confirmé), cancelled (annulé)
- **Couleurs**: Selon le statut et le type (projet/congé)

#### Affichage des congés
- **Congés approuvés**: Visibles automatiquement dans le planning
- **Code couleur**: Couleur sombre avec motif hachuré
- **Mode lecture seule**: Les congés ne sont pas modifiables depuis le planning
- **Type affiché**: Titre indique le type de congé (CP, RTT, etc.)
- **Impact disponibilité**: Pris en compte dans les calculs de charge

#### Taux de staffing hebdomadaire
- **Calcul automatique**: Par contributeur et par semaine
- **Affichage**: Badges colorés dans les libellés de ressources
- **Indicateurs visuels**:
  - Normal: < 80% (badge standard)
  - Élevé: 80-100% (badge warning)
  - Surcharge: > 100% (badge danger)
- **Détection**: Alerte visuelle en cas de surcharge

#### Filtres disponibles
- **Contributeurs**: Filtrer par contributeur(s) spécifique(s)
- **Chefs de projet**: Afficher uniquement les projets d'un chef de projet
- **Projets**: Filtrer par projet(s)
- **Types de projet**: Forfait, Régie, Interne, Client
- **Période**: Nombre de semaines affichées (configurable)
- **Sauvegarde**: Filtres sauvegardés dans l'URL pour partage facile

#### Semaines complétées
- **Read-only**: Les planifications des semaines terminées sont en lecture seule
- **Affichage**: Visuellement distinctes (opacité réduite ou indicateur)
- **Modification**: Uniquement via la saisie des temps, pas depuis le planning

### Règles métier

#### Contraintes de durée
- Chaque jour ne peut durer que le nombre d'heures de travail du collaborateur
- Si une tâche dépasse cette durée, elle doit être étalée sur plusieurs jours
- La somme des planifications par jour ne doit pas excéder la durée quotidienne

#### Détection de surcharge
- Si la somme des heures planifiées > heures disponibles: dépassement visible
- Indicateur visuel (badge rouge) sur le contributeur surchargé
- Alertes dans le tableau de bord pour les managers

### Sécurité
- Endpoints protégés par CSRF et rôles
- Mise à jour en AJAX avec validation côté serveur
- Vérification des permissions pour chaque action

## Workflow de Gestion des Congés

### Pour les intervenants

#### Interface de demande
- **URL**: `/vacation-request`
- **Menu**: Delivery → Mes congés
- **Accès**: Tous les contributeurs (ROLE_INTERVENANT)

#### Types de congés disponibles
- **Congés payés (CP)**: Congés annuels standards
- **RTT**: Réduction du temps de travail
- **Congé sans solde**: Congé exceptionnel non payé
- **Maladie**: Arrêt maladie
- **Formation**: Congé de formation professionnelle
- **Autre**: Autres types de congés spécifiques

#### Saisie des demandes
- Dates de début et fin (calendrier)
- Type de congé (sélecteur)
- Notes optionnelles (commentaires, justificatifs)
- Validation du formulaire avec vérification des dates
- Soumission pour validation au manager

#### Visualisation de l'historique
- **Demandes en attente**: Statut "pending", en cours de validation
- **Demandes approuvées**: Statut "approved", congés confirmés
- **Demandes rejetées**: Statut "rejected", avec motif de refus éventuel
- **Filtres**: Par statut, par période
- **Indicateurs**: Nombre de jours demandés vs disponibles

#### Règles de modification
- **Demandes futures**: Modifiables par le contributeur avant validation
- **Demandes passées**: Non modifiables par le contributeur
- **Seuls les managers/compta**: Peuvent modifier les congés passés

#### Solde de congés
- Visualisation du solde disponible sur l'année courante
- Affichage des congés cumulables sur n+1 (0,8 jours CP par mois travaillé)
- Historique de consommation des congés

### Pour les managers

#### Interface de validation
- **URL**: `/vacation-approval`
- **Menu**: Delivery → Validation congés
- **Accès**: `ROLE_MANAGER` et au-dessus

#### Notification dans le header
- Compteur de demandes en attente (badge rouge)
- Cliquable pour accès direct à la page de validation
- Auto-refresh toutes les 60 secondes
- Notification en temps réel via Symfony Messenger

#### Widget page d'accueil
- Affichage des 5 dernières demandes en attente
- Informations résumées: contributeur, dates, type, durée
- Lien vers le détail complet
- Actions rapides: Approuver/Rejeter

#### Détail des demandes
- Nom du contributeur et profil
- Type de congé et durée (nombre de jours)
- Dates de début et fin
- Notes du contributeur (si présentes)
- Solde de congés du contributeur
- Impact sur le planning (conflits éventuels)

#### Actions disponibles
- **Approuver**: Valide la demande et crée le congé dans le système
- **Rejeter**: Refuse la demande avec possibilité de commentaire
- **Reporter**: Marquer comme "à revoir" pour traitement ultérieur
- **Commentaires**: Ajouter des notes internes

#### Notifications automatiques
- **Après approbation**: Email envoyé au contributeur avec confirmation
- **Après rejet**: Email avec motif de refus (si fourni)
- **Rappels**: Notification si demandes en attente depuis > 7 jours

### Hiérarchie et notifications

#### Rattachement contributeur → manager
- Configuration dans Administration → Contributeurs
- Champ "Manager" (relation Many-to-One vers User/Contributor)
- Un contributeur = un manager direct
- Permet la délégation de validation

#### Système de notifications
- **Technologie**: Symfony Messenger (asynchrone)
- **Canaux**: Email + notifications interface
- **Types**:
  - Nouvelle demande pour le manager
  - Approbation pour le contributeur
  - Rejet pour le contributeur
  - Rappel pour demandes en attente
- **Configuration**: Templates d'emails personnalisables

#### Auto-refresh
- Compteur de notifications: refresh toutes les 60 secondes
- Polling AJAX vers endpoint `/vacation-approval/count`
- Mise à jour du badge sans rechargement de page
- Performance: Query optimisée avec COUNT()

### Intégration avec le planning

#### Affichage automatique
- Congés approuvés visibles immédiatement dans `/planning`
- Synchronisation en temps réel (ou via cache court)
- Affichage sur la ligne du contributeur concerné

#### Caractéristiques visuelles
- **Couleur**: Sombre (noir/gris foncé) vs couleur claire pour projets
- **Motif**: Hachuré (striped pattern CSS)
- **Opacité**: Légèrement transparente
- **Icône**: Pictogramme congé (🏖️ ou similaire)
- **Titre**: Type de congé + dates

#### Mode lecture seule
- Pas de drag & drop sur les congés
- Pas de resize
- Pas de modification via modal
- Tooltip informatif au survol
- Lien vers la fiche de demande (managers uniquement)

#### Calculs de disponibilité
- Congés déduits de la disponibilité hebdomadaire
- Pris en compte dans le calcul du taux de staffing
- Impact sur les recommandations d'optimisation IA
- Alertes si planification sur période de congé approuvé

### Cas d'usage typiques

#### Scénario 1: Demande simple
1. Contributeur se connecte et va dans "Mes congés"
2. Saisit une demande de congés payés du 15/12 au 20/12
3. Manager reçoit notification (email + compteur header)
4. Manager approuve depuis le widget ou la page dédiée
5. Contributeur reçoit email de confirmation
6. Congés apparaissent automatiquement dans le planning

#### Scénario 2: Conflit de planning
1. Contributeur demande congés sur une période déjà planifiée
2. Manager voit l'alerte de conflit dans la page de validation
3. Manager contacte le chef de projet concerné
4. Après discussion, le planning est ajusté
5. Manager approuve la demande de congés
6. Le planning affiche les congés en lecture seule

#### Scénario 3: Demande en urgence
1. Contributeur tombe malade et demande un arrêt maladie
2. Manager reçoit notification immédiate
3. Manager approuve rapidement (type "Maladie" = procédure simplifiée)
4. Le planning est automatiquement mis à jour
5. Les recommandations d'optimisation tiennent compte de l'absence

## Optimisation du planning (IA)

Pour les fonctionnalités d'optimisation intelligente du planning avec analyse TACE et recommandations IA, voir la documentation dédiée: **[docs/planning-ai.md](./planning-ai.md)**

### Lien avec la gestion du temps

L'optimisation IA utilise les données de:
- **Planifications futures**: Pour calculer la charge prévisionnelle
- **Congés approuvés**: Pour déduire la disponibilité réelle
- **Temps passés**: Pour analyser les tendances historiques
- **Profils métier**: Pour suggérer des réaffectations compatibles

### Intégration dans le workflow

1. Les managers accèdent à `/planning/optimization`
2. Le système analyse automatiquement:
   - Les taux de staffing par contributeur
   - Les congés approuvés et planifiés
   - Les surcharges et sous-utilisations
3. L'IA génère des recommandations actionnables
4. Les managers peuvent ajuster le planning en conséquence
5. Les congés approuvés restent intouchables (contraintes fixes)
