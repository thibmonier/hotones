# 📋 Fonctionnalités principales

## 🔐 Authentification & Sécurité
- Connexion sécurisée avec email/mot de passe
- 2FA obligatoire via Google Authenticator (TOTP)
- Gestion des profils utilisateurs (nom, prénom, adresse, téléphone, avatar)

## Gestion de mon compte
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

## Gestion des devis d'un projet
- Order : Devis
  - Numéro unique: D[année][mois][incrément]
  - Statuts: A signer, Gagné, Signé, Perdu, Terminé, StandBy, Abandonné
  - Mise à jour rapide du statut:
    - Depuis la page d’un devis: sélecteur dans le panneau « Actions » (POST sécurisé CSRF)
    - Depuis la liste des devis: sélecteur dans la colonne « Statut » (soumission auto au changement)
    - Route: POST /orders/{id}/status (name: order_update_status)
  - Sections regroupant des lignes + totalisation
- Lignes: profil, TJM, jours, total (jours×TJM), achats attachés (affiche marge nette)

## 🧪 Tests E2E
- Outil: Symfony Panther (Chrome headless)
- Parcours couverts: authentification (login), navigation tableau de bord → projets, création d’un projet (flux minimal)
- Commande: `./vendor/bin/phpunit` (voir `docs/tests.md` pour variables Chrome)
- CI: exécution automatique des E2E via GitHub Actions (`.github/workflows/ci.yml`)
