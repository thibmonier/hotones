# 📋 Fonctionnalités principales

## 🔐 Authentification & Sécurité
- Connexion sécurisée avec email/mot de passe
- 2FA obligatoire via Google Authenticator (TOTP)
- Gestion des profils utilisateurs (nom, prénom, adresse, téléphone, avatar)

## Gestion de mon compte

Référence: Roadmap — Lot 4 (Gestion de Compte Utilisateur) → [docs/roadmap-lots.md](./roadmap-lots.md)
- dans le header, mon avatar et mon prénom doivent être présents à la place de l'avatar par défaut du thème et "Henry"
- à l'ouverture du menu, il faut que le lien "Profile" soit transformé en "Mon compte" et renvoit vers une page permettant de gérer mon compte (actuellement "/me")
- Cette page de compte doit pouvoir me permettre de gérer mes informations personnelles (nom, prénom, mail, téléphone professionnel en optionnel, téléphone personnel, adresse personnelle) et les informations de connexion (mot de passe, 2FA)
- Cette page doit reprendre les informations de ma carrière en mode lecture seule
- il faudrait retirer les entrées My wallet, Settings et Lock screen du menu d'entête
- dans le menu d'entête, il faudrait que le lien logout soit modifié en "Déconnexion" comme dans le menu vertical et que l'ensemble de la section "mon compte" soit retirée du menu vertical
- dans la gestion de compte, un utilisateur doit pouvoir associer un avatar à son compte

## 👥 Gestion des utilisateurs & intervenants
- User : Compte utilisateur avec authentification 2FA
- Contributor : Intervenants sur les projets (peut être lié à un User)
- EmploymentPeriod : Historique RH (salaire, CJM, TJM, heures hebdo, temps partiel, dates, profils)

## 📊 Gestion des projets
- Project : Projets client
  - Nom du projet et client, jours vendus, TJM de vente, dates, achats
  - Devis du projet, consommation et rentabilité par devis + vue consolidée
  - Contingence par devis (impacte la rentabilité sans changer le prix de vente)
  - Projet interne/externe; affichage en jours (1j=8h) et euros
  - 2 tâches par défaut (AVV, Non-vendu) hors calcul rentabilité
  - Métadonnées: technologies, offre/service
  - Rôles associés: KAM, Chef de projet, Directeur de projet, Commercial identificateur
  - Types de projet: forfait (périmètre/échéancier/budget fixes) ou régie (facturé au temps passé)
  - Listing projets: colonne « Type » affiche désormais le type métier Forfait/Régie, avec un badge secondaire « Interne/Client »

## ⏱️ Saisie des temps & compteur
- Saisie hebdomadaire par projet/tâche (pas de sous-tâche obligatoire)
- Historique personnel et vue globale par mois
- Compteur de temps intégré:
  - Démarrer/arrêter depuis la page de saisie; un seul compteur actif par contributeur
  - À l’arrêt, imputation automatique sur la tâche du jour avec minimum de 0,125j (1h)
  - Démarrer un nouveau compteur stoppe et impute le précédent

## Gestion des devis d'un projet
- Order : Devis
  - Numéro unique: D[année][mois][incrément]
  - Statuts: A signer, Gagné, Signé, Perdu, Terminé, StandBy, Abandonné
  - Contractualisation: `forfait` (échéancier) ou `regie` (temps passé)
    - Forfait: échéancier de paiement (lignes avec date + montant en % du total devis ou montant fixe). La somme doit couvrir 100% du devis (avertissement si ≠ 100%).
    - Régie: facturation mensuelle basée sur les temps saisis (Σ heures × (TJM contributeur / 8)).
  - Mise à jour rapide du statut:
    - Depuis la page d’un devis: sélecteur dans le panneau « Actions » (POST sécurisé CSRF)
    - Depuis la liste des devis: sélecteur dans la colonne « Statut » (soumission auto au changement)
    - Route: POST /orders/{id}/status (name: order_update_status)
  - Sections regroupant des lignes + totalisation
  - Lignes: profil, TJM, jours, total (jours×TJM), achats attachés (affiche marge nette)

## 📊 Dashboard de Suivi du Staffing
- URL : `/staffing/dashboard`
- Menu : Administration > Analyses & Rapports > 📈 Staffing & TACE
- Graphiques d'évolution sur -6 mois à aujourd'hui :
  - Taux de staffing : (Temps staffé / Temps disponible) × 100
  - TACE (Taux d'Activité Congés Exclus) : (Jours produits / Jours travaillés hors congés) × 100
- Tableaux de métriques par profil et par contributeur
- Filtres : contributeur, profil, granularité (hebdo/mensuel/trimestriel)
- Modèle en étoile : DimProfile, DimTime, FactStaffingMetrics
- Commande CLI : `php bin/console app:calculate-staffing-metrics`
- Calcul automatique des jours disponibles, travaillés, staffés et congés

## 📅 Planning Resource Timeline
- URL : `/planning`
- Menu : Delivery > Planning
- Vue timeline avec FullCalendar Scheduler (une ligne par contributeur)
- **Fonctionnalités principales** :
  - Création de planifications par drag & select
  - Modification des dates par drag & drop ou resize
  - Édition complète via modal (dates, heures/jour, statut, notes)
  - Division de planifications (split) pour répartir sur plusieurs périodes
- **Affichage des congés** :
  - Congés approuvés visibles avec code couleur sombre et motif hachuré
  - Non modifiables (lecture seule)
  - Affichage du type de congé dans le titre
- **Taux de staffing hebdomadaire** :
  - Calcul automatique par contributeur et par semaine
  - Affichage en badges colorés dans les libellés de ressources
  - Indicateurs visuels : normal (<80%), élevé (80-100%), surcharge (>100%)
- **Filtres** :
  - Contributeurs, chefs de projet, projets, types de projet
  - Période configurable (nombre de semaines)
  - Sauvegarde des filtres dans l'URL
- **Semaines complétées** : read-only pour les planifications terminées

## 🤖 Optimisation IA du Planning
- URL : `/planning/optimization`
- Menu : Delivery > Optimisation (ROLE_MANAGER requis)
- **Documentation complète** : [docs/planning-ai.md](./planning-ai.md)
- **Analyse TACE automatique** :
  - Détection des contributeurs en surcharge (>90% ou >110% critique)
  - Détection des contributeurs sous-utilisés (<70% ou <50% critique)
  - Classification par niveau de sévérité
  - Calcul des écarts par rapport à l'idéal (70-90%)
- **Génération de recommandations** :
  - Réaffectation de projets entre contributeurs surchargés et sous-utilisés
  - Prise en compte des profils métier compatibles
  - Priorisation selon les niveaux de service client (VIP/Priority)
  - Actions concrètes et impact estimé pour chaque recommandation
- **Intégration IA** :
  - Support OpenAI (GPT-4o-mini) : ~$0.05/mois pour 100 analyses
  - Support Anthropic (Claude 3.5 Haiku) : ~$0.48/mois pour 100 analyses
  - Activation automatique si clé API configurée dans `.env`
  - Enrichissement contextuel des recommandations
  - Priorité : OpenAI utilisé si les deux clés sont présentes
- **Dashboard d'optimisation** :
  - Résumé : contributeurs critiques, surchargés, sous-utilisés
  - Liste détaillée des recommandations triées par priorité
  - Indicateurs visuels (badges de sévérité)
  - Détail des contributeurs par catégorie avec leur TACE
- **Alertes intégrées** :
  - Bannière d'avertissement dans `/planning` pour les managers
  - Affichage du nombre de situations critiques
  - Lien direct vers les recommandations

## 🏖️ Workflow de Gestion des Congés
- **Pour les intervenants** :
  - URL : `/vacation-request`
  - Menu : Delivery > Mes congés
  - Types de congés : Congés payés, RTT, Congé sans solde, Maladie, Formation, Autre
  - Saisie des dates de début et fin, notes optionnelles
  - Visualisation de l'historique des demandes (en attente, approuvées, rejetées)
- **Pour les managers** :
  - URL : `/vacation-approval`
  - Menu : Delivery > Validation congés
  - Notification dans le header avec compteur de demandes en attente
  - Widget sur la page d'accueil avec les 5 dernières demandes
  - Détail de chaque demande avec actions Approuver/Rejeter
  - Envoi automatique d'emails de notification (approbation/rejet)
- **Hiérarchie et notifications** :
  - Chaque contributeur peut être rattaché à un manager
  - Configuration dans Administration > Contributeurs
  - Notifications en temps réel via Symfony Messenger
  - Auto-refresh du compteur de notifications toutes les 60 secondes
- **Intégration planning** :
  - Congés approuvés affichés automatiquement dans le planning
  - Code couleur sombre avec motif hachuré (non modifiable)
  - Prise en compte dans les calculs de disponibilité

## 👔 Niveaux de Service Client
- **4 niveaux disponibles** :
  - **VIP** : Top 20 du CA annuel par client
  - **Prioritaire** : Top 50 du CA annuel par client
  - **Standard** : Clients standards (par défaut)
  - **Basse priorité** : CA annuel < 5000€
- **Modes de calcul** :
  - **Automatique** : calcul basé sur le CA annuel (déterminé par les devis signés)
  - **Manuel** : niveau défini manuellement sur la fiche client
  - Basculement possible entre les deux modes
- **Configuration** :
  - Sélection du mode dans la fiche client (création/édition)
  - Si mode manuel : sélecteur de niveau (VIP, Priority, Standard, Low)
  - Si mode auto : niveau calculé automatiquement et affiché en lecture seule
- **Commande de recalcul** :
  - `php bin/console app:client:recalculate-service-level --year=2024`
  - Recalcule tous les clients en mode automatique pour l'année donnée
  - Utile après import de nouvelles données ou changement de règles
- **Affichage** :
  - Badges colorés dans toute l'application
  - Prise en compte dans les recommandations d'optimisation (priorisation)
  - Affiché sur les fiches clients et dans les listings

## 🧪 Tests E2E
- Outil: Symfony Panther (Chrome headless)
- Parcours couverts: authentification (login), navigation tableau de bord → projets, création d'un projet (flux minimal)
- Commande: `./vendor/bin/phpunit` (voir `docs/tests.md` pour variables Chrome)
- CI: exécution automatique des E2E via GitHub Actions (`.github/workflows/ci.yml`)
