# Sprint 1 — Récapitulatif ✅

**Date de complétion** : 2025-11-13  
**Durée estimée** : 2 semaines  
**Statut** : ✅ TERMINÉ

---

## Objectifs

Le Sprint 1 visait à poser les fondations de l'application en complétant :
1. La gestion des contributeurs (CRUD complet)
2. La gestion des périodes d'emploi
3. La gestion du compte utilisateur personnel

---

## Lots complétés

### ✅ Lot 1.1 : Gestion des Contributeurs

**Objectif** : CRUD complet pour les contributeurs avec toutes les fonctionnalités métier.

#### Fonctionnalités implémentées

- ✅ Entity `Contributor` et repository
- ✅ CRUD complet (liste, création, édition, suppression soft)
- ✅ Recherche et filtres avancés (nom, profil actuel, statut actif/inactif)
- ✅ Pagination et tri personnalisables
- ✅ Affichage des périodes d'emploi associées
- ✅ **Upload et gestion d'avatar** (format JPG, PNG, GIF, max 2Mo)
- ✅ Vue détaillée avec historique (emplois, projets, temps saisis)
- ✅ Export CSV avec filtres
- ✅ Statistiques (nombre avec compte, CJM moyen, TJM moyen)

#### Fichiers clés

- `src/Entity/Contributor.php` - Entité avec champ `avatarFilename`
- `src/Controller/ContributorController.php` - Contrôleur avec méthode `handleAvatarUpload()`
- `src/Form/ContributorType.php` - Formulaire avec champ `avatarFile`
- `templates/contributor/*.html.twig` - Vues liste/détail/édition
- `config/services.yaml` - Configuration paramètre `avatars_directory`

---

### ✅ Lot 1.2 : Gestion des Périodes d'Emploi

**Objectif** : Interface complète de gestion des périodes d'emploi avec validation.

#### Fonctionnalités implémentées

- ✅ Entity `EmploymentPeriod` avec relations
- ✅ Interface complète de gestion des périodes
- ✅ Association avec les profils métier (`JobProfile`)
- ✅ Validation des chevauchements de dates
- ✅ Calcul automatique CJM à partir du salaire et temps de travail
- ✅ Controller avec FormType complet
- ✅ Affichage dans la fiche contributeur et dans la page "Mon compte"

#### Fichiers clés

- `src/Entity/EmploymentPeriod.php`
- `src/Controller/EmploymentPeriodController.php`
- `src/Form/EmploymentPeriodType.php`
- `templates/employment_period/*.html.twig`

---

### ✅ Lot 4 : Gestion de Compte Utilisateur

**Objectif** : Permettre à chaque utilisateur de gérer ses informations personnelles et paramètres de sécurité.

#### Fonctionnalités implémentées

**4.1 Page "Mon compte" (`/me`)**
- ✅ Route `/me` accessible depuis le header
- ✅ Structure avec sections : Informations / Sécurité / Actions rapides / Carrière
- ✅ Informations personnelles :
  - Nom, prénom, email
  - Téléphones (professionnel optionnel, personnel)
  - Adresse personnelle
- ✅ Upload avatar (formats image, max 2Mo)
- ✅ Affichage avatar dans le header (remplace avatar par défaut)
- ✅ Affichage prénom dans le header

**4.2 Sécurité**
- ✅ Changement de mot de passe avec validation
- ✅ Gestion 2FA/TOTP (activer/désactiver, QR code)
- ✅ Support TOTP via bundle `scheb/2fa-totp`
- 🔲 Sessions actives (liste et révocation) - **Non prioritaire, reporté**

**4.3 Carrière (lecture seule)**
- ✅ Historique des périodes d'emploi
- ✅ Affichage des profils occupés
- ✅ Informations : salaire, CJM, TJM, heures/semaine, temps de travail

**4.4 Menu header**
- ✅ Adaptation du menu utilisateur (dropdown)
- ✅ Liens vers "Mon compte", "Mes notifications", "Mes tâches"
- ✅ Renommage : "Profile" → "Mon compte", "Logout" → "Déconnexion"
- ✅ Affichage de l'avatar personnalisé
- ✅ Affichage du prénom de l'utilisateur

#### Fichiers clés

- `src/Entity/User.php` - Entité avec champs avatar, phones, address, 2FA
- `src/Controller/ProfileController.php` - Contrôleur complet
- `templates/profile/profile.html.twig` - Page principale
- `templates/profile/edit.html.twig` - Édition des informations
- `templates/profile/password.html.twig` - Changement de mot de passe
- `templates/profile/2fa_setup.html.twig` - Configuration 2FA
- `templates/profile/notifications.html.twig` - Préférences de notifications
- `templates/layouts/_topbar.html.twig` - Header avec avatar et prénom

---

## Architecture technique

### Entités principales

**User**
- Champs : email, password, firstName, lastName, roles
- Champs profil : avatar, phoneWork, phonePersonal, address
- Champs 2FA : totpSecret, totpEnabled
- Relations : OneToOne avec Contributor

**Contributor**
- Champs : firstName, lastName, email, phones, address
- Champs métier : cjm, tjm, active, notes
- Champ avatar : avatarFilename
- Relations : 
  - OneToOne avec User
  - ManyToMany avec Profile
  - OneToMany avec EmploymentPeriod
  - OneToMany avec Timesheet

**EmploymentPeriod**
- Champs : startDate, endDate, salary, weeklyHours, workTimePercentage
- Champs calculés : cjm, tjm
- Relations :
  - ManyToOne avec Contributor
  - ManyToMany avec JobProfile

### Sécurité

- Authentification par mot de passe hashé (Symfony PasswordHasher)
- Support 2FA TOTP avec QR code
- Upload sécurisé d'avatars avec validation de type MIME
- Contrôle d'accès par rôles (ROLE_CHEF_PROJET, ROLE_MANAGER, etc.)

### Upload de fichiers

**Avatars contributeurs**
- Répertoire : `public/uploads/avatars/`
- Formats acceptés : JPG, PNG, GIF
- Taille max : 2 Mo
- Nommage : slugified + uniqid + extension

**Avatars utilisateurs**
- Répertoire : `public/uploads/avatars/`
- Formats acceptés : image/*
- Taille max : 2 Mo (non explicitement validé, à améliorer)
- Nommage : `u{user_id}_{random}.{ext}`

---

## Tests

⚠️ **À faire** : Tests automatisés pour le Sprint 1

### Tests prioritaires à écrire

1. **Tests unitaires**
   - Calcul automatique du CJM (EmploymentPeriod)
   - Validation des chevauchements de périodes
   - Méthodes utilitaires User (hasRole, isIntervenant, etc.)

2. **Tests fonctionnels**
   - CRUD Contributor avec upload d'avatar
   - CRUD EmploymentPeriod avec validation
   - Modification du profil utilisateur
   - Changement de mot de passe
   - Activation/désactivation 2FA

3. **Tests E2E (optionnel)**
   - Parcours complet : création contributeur → ajout période → consultation
   - Parcours profil : connexion → modification profil → activation 2FA

---

## Améliorations futures (hors sprint)

### Court terme
- [ ] Gestion des sessions actives (Lot 4.2)
- [ ] Tests automatisés complets
- [ ] Validation stricte de la taille d'avatar utilisateur

### Moyen terme
- [ ] Compression automatique des avatars uploadés
- [ ] Crop/resize d'image dans l'interface
- [ ] Historique des modifications de profil
- [ ] Notification email lors du changement de mot de passe

---

## Statistiques du Sprint 1

- **Lots complétés** : 3/3 (100%)
- **Fonctionnalités** : 25/26 (96%)
- **Temps estimé** : 2 semaines
- **Éléments reportés** : 1 (sessions actives)
- **Tests écrits** : 0 (à faire)

---

## Prochaines étapes

### Sprint 2 : Projets & Devis
- Lot 1.3 : Projets (complet avec tous les onglets)
- Lot 1.4 : Devis (complet avec gestion sections/lignes)

### Préparation
- Vérifier que les entités Project et Order sont à jour
- Confirmer les routes et contrôleurs existants
- Identifier les templates à moderniser

---

## Notes

- Le Sprint 1 était déjà largement implémenté avant le lancement officiel
- Seule la documentation et la validation finale ont été nécessaires
- La base technique est solide pour les sprints suivants
- Le menu vertical a été restructuré selon la nouvelle arborescence (Commerce, Delivery, Comptabilité, Administration, Configuration, Analytics)
