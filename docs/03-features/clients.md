# Gestion des Clients

Le module de gestion des clients permet de centraliser les informations sur les entreprises clientes et leurs contacts associés.

## Table des matières

- [Vue d'ensemble](#vue-densemble)
- [Entités](#entités)
- [Fonctionnalités](#fonctionnalités)
- [Routes](#routes)
- [Permissions](#permissions)
- [Upload de logo](#upload-de-logo)
- [Interface utilisateur](#interface-utilisateur)

---

## Vue d'ensemble

Le système de gestion des clients se compose de deux entités principales:

1. **Client**: Représente une entreprise cliente
2. **ClientContact**: Représente les personnes de contact au sein de l'entreprise cliente

**Relation:** Un client peut avoir plusieurs contacts (relation OneToMany).

**Cas d'usage typiques:**
- Centraliser les informations clients (coordonnées, logo, site web)
- Gérer plusieurs contacts par client (décideurs, contacts techniques, etc.)
- Associer les clients aux projets
- Référencer rapidement les interlocuteurs

---

## Entités

### Client

**Fichier:** `src/Entity/Client.php`

Représente une entreprise ou organisation cliente.

**Champs:**

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | Non | Identifiant unique |
| `name` | string(180) | Non | Nom de l'entreprise cliente |
| `logoPath` | string(255) | Oui | Chemin vers le logo (relatif à `/public`) |
| `website` | string(255) | Oui | URL du site web |
| `description` | text | Oui | Description libre (notes, contexte, etc.) |
| `contacts` | Collection | - | Collection de `ClientContact` (OneToMany) |

**Relations:**
- **OneToMany** avec `ClientContact` (inversedBy: `client`)
  - Cascade: `persist`, `remove`
  - orphanRemoval: `true`

**Méthodes utiles:**
```php
public function getName(): string
public function setName(string $name): self
public function getLogoPath(): ?string
public function setLogoPath(?string $logoPath): self
public function getWebsite(): ?string
public function setWebsite(?string $website): self
public function getDescription(): ?string
public function setDescription(?string $description): self
public function getContacts(): Collection<int, ClientContact>
public function addContact(ClientContact $contact): self
public function removeContact(ClientContact $contact): self
public function __toString(): string  // Retourne $name
```

**Exemple:**
```php
$client = new Client();
$client->setName('Acme Corporation')
    ->setWebsite('https://acme.example.com')
    ->setDescription('Client historique, projet e-commerce depuis 2022')
    ->setLogoPath('/uploads/clients/client_abc123.png');
```

---

### ClientContact

**Fichier:** `src/Entity/ClientContact.php`

Représente une personne de contact au sein d'une entreprise cliente.

**Champs:**

| Champ | Type | Nullable | Description |
|-------|------|----------|-------------|
| `id` | integer | Non | Identifiant unique |
| `client` | Client | Non | Client associé (ManyToOne) |
| `lastName` | string(100) | Non | Nom de famille |
| `firstName` | string(100) | Non | Prénom |
| `email` | string(180) | Oui | Adresse email |
| `phone` | string(50) | Oui | Téléphone fixe |
| `mobilePhone` | string(50) | Oui | Téléphone mobile |
| `positionTitle` | string(120) | Oui | Poste / fonction |
| `active` | boolean | Non | Contact actif (défaut: true) |

**Relations:**
- **ManyToOne** avec `Client` (inversedBy: `contacts`)

**Méthodes utiles:**
```php
public function getClient(): ?Client
public function setClient(?Client $client): self
public function getLastName(): string
public function setLastName(string $lastName): self
public function getFirstName(): string
public function setFirstName(string $firstName): self
public function getEmail(): ?string
public function setEmail(?string $email): self
public function getPhone(): ?string
public function setPhone(?string $phone): self
public function getMobilePhone(): ?string
public function setMobilePhone(?string $mobilePhone): self
public function getPositionTitle(): ?string
public function setPositionTitle(?string $positionTitle): self
public function isActive(): bool
public function setActive(bool $active): self
public function getFullName(): string  // Retourne "{firstName} {lastName}"
```

**Exemple:**
```php
$contact = new ClientContact();
$contact->setClient($client)
    ->setFirstName('John')
    ->setLastName('Doe')
    ->setEmail('john.doe@acme.example.com')
    ->setPhone('+33 1 23 45 67 89')
    ->setMobilePhone('+33 6 12 34 56 78')
    ->setPositionTitle('Directeur Digital')
    ->setActive(true);

$client->addContact($contact);
```

---

## Fonctionnalités

### CRUD Clients

Le module offre un CRUD complet pour les clients :

1. **Liste (Index)**
   - Affichage de tous les clients
   - Tri par nom
   - Affichage du logo si présent

2. **Création (New)**
   - Formulaire de création client
   - Upload de logo
   - Validation des champs

3. **Consultation (Show)**
   - Fiche client complète
   - Liste des contacts associés
   - Logo, site web, description

4. **Modification (Edit)**
   - Modification des informations client
   - Remplacement du logo

5. **Suppression (Delete)**
   - Suppression du client
   - Suppression en cascade des contacts (orphanRemoval)

### Gestion des Contacts

Chaque client peut avoir plusieurs contacts:

1. **Ajout de contact**
   - Depuis la fiche client
   - Formulaire dédié
   - Lien automatique avec le client

2. **Contacts multiples**
   - Contacts actifs/inactifs
   - Différentes fonctions (décideur, technique, facturation, etc.)

3. **Suppression automatique**
   - Les contacts sont supprimés si le client est supprimé (orphanRemoval)

---

## Routes

**Contrôleur:** `ClientController`
**Préfixe:** `/clients`

| Route | Méthode | Nom | Action | Permission |
|-------|---------|-----|--------|------------|
| `/clients` | GET | `client_index` | Liste des clients | `ROLE_INTERVENANT` |
| `/clients/new` | GET, POST | `client_new` | Créer un client | `ROLE_CHEF_PROJET` |
| `/clients/{id}` | GET | `client_show` | Afficher un client | `ROLE_INTERVENANT` |
| `/clients/{id}/edit` | GET, POST | `client_edit` | Modifier un client | `ROLE_CHEF_PROJET` |
| `/clients/{id}/delete` | POST | `client_delete` | Supprimer un client | `ROLE_MANAGER` |
| `/clients/{id}/contacts/new` | GET, POST | `client_contact_new` | Ajouter un contact | `ROLE_CHEF_PROJET` |

**Exemples:**
```
/clients                       → Liste tous les clients
/clients/new                   → Formulaire de création
/clients/42                    → Fiche du client #42
/clients/42/edit               → Modification du client #42
/clients/42/delete             → Suppression du client #42
/clients/42/contacts/new       → Ajouter un contact au client #42
```

---

## Permissions

Les permissions sont gérées par les attributs `#[IsGranted()]` sur les méthodes du contrôleur.

| Action | Rôle requis | Justification |
|--------|-------------|---------------|
| Consulter (`index`, `show`) | `ROLE_INTERVENANT` | Tous les intervenants peuvent voir les clients |
| Créer / Modifier | `ROLE_CHEF_PROJET` | Seuls les chefs de projet gèrent les clients |
| Supprimer | `ROLE_MANAGER` | Action sensible réservée aux managers |
| Ajouter contact | `ROLE_CHEF_PROJET` | Même niveau que création/modification client |

**Hiérarchie des rôles:**
```
ROLE_ADMIN
  └─ ROLE_MANAGER
      └─ ROLE_CHEF_PROJET
          └─ ROLE_INTERVENANT
```

**Note:** Un utilisateur avec `ROLE_MANAGER` a automatiquement `ROLE_CHEF_PROJET` et `ROLE_INTERVENANT`.

Voir [Documentation Rôles](roles.md) pour plus de détails.

---

## Upload de logo

### Fonctionnement

L'upload de logo se fait via un champ `file` standard:

**HTML:**
```html
<form method="post" enctype="multipart/form-data">
    <input type="file" name="logo" accept="image/*">
    <!-- autres champs -->
    <button type="submit">Enregistrer</button>
</form>
```

**Traitement côté serveur:**
```php
/** @var UploadedFile|null $logo */
$logo = $request->files->get('logo');
if ($logo instanceof UploadedFile && $logo->isValid()) {
    $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/clients';
    $fs = new Filesystem();
    if (!$fs->exists($uploadDir)) {
        $fs->mkdir($uploadDir, 0775);
    }
    $safeName = uniqid('client_', true).'.'.$logo->guessExtension();
    $logo->move($uploadDir, $safeName);
    $client->setLogoPath('/uploads/clients/'.$safeName);
}
```

### Emplacement des fichiers

**Dossier de stockage:** `public/uploads/clients/`

**Format du nom de fichier:** `client_{uniqid}.{extension}`

**Exemples:**
```
public/uploads/clients/client_6543f2a1b3c45.png
public/uploads/clients/client_6543f2a1b3c46.jpg
public/uploads/clients/client_6543f2a1b3c47.svg
```

**Path stocké en base:** `/uploads/clients/client_6543f2a1b3c45.png`

### Affichage du logo

Dans les templates Twig:
```twig
{% if client.logoPath %}
    <img src="{{ client.logoPath }}" alt="Logo {{ client.name }}" width="150">
{% else %}
    <div class="placeholder-logo">{{ client.name|slice(0, 1) }}</div>
{% endif %}
```

### Formats acceptés

Le système accepte tous les formats d'image courants:
- PNG
- JPEG / JPG
- GIF
- SVG
- WebP

**Extension détectée automatiquement:** via `$logo->guessExtension()`

### Gestion des erreurs

Si l'upload échoue:
- Le logo n'est pas enregistré
- Le client est créé/modifié sans logo
- Aucune erreur bloquante

**Validation:**
- `$logo->isValid()` vérifie que l'upload s'est bien déroulé
- Pas de limitation de taille spécifique (utilise limites PHP par défaut)

### Sécurité

**Bonnes pratiques implémentées:**

1. **Nom de fichier aléatoire:** `uniqid()` évite les collisions et l'écrasement
2. **Extension basée sur le type MIME:** `guessExtension()` empêche l'upload de fichiers malveillants
3. **Dossier dédié:** Séparation par type de ressource
4. **Permissions:** 0775 sur le dossier de destination

**À améliorer (recommandations):**
- Valider le type MIME explicitement
- Limiter la taille du fichier
- Redimensionner automatiquement les images trop grandes
- Supprimer l'ancien logo lors du remplacement

---

## Interface utilisateur

### Page de liste (`/clients`)

**Template:** `templates/client/index.html.twig`

**Affichage:**
- Tableau de tous les clients
- Colonnes: Logo, Nom, Site web, Nombre de contacts
- Bouton "Nouveau client" (si `ROLE_CHEF_PROJET`)
- Liens vers la fiche de chaque client

**Tri:** Par nom alphabétique (via `ClientRepository::findAllOrderedByName()`)

### Fiche client (`/clients/{id}`)

**Template:** `templates/client/show.html.twig`

**Sections:**
1. **Informations principales**
   - Logo (si présent)
   - Nom
   - Site web (lien cliquable)
   - Description

2. **Contacts**
   - Liste des contacts avec:
     - Nom complet
     - Poste
     - Email
     - Téléphones
     - Statut actif/inactif
   - Bouton "Ajouter un contact" (si `ROLE_CHEF_PROJET`)

3. **Projets associés** (si relation implémentée)
   - Liste des projets du client

4. **Actions**
   - Modifier (si `ROLE_CHEF_PROJET`)
   - Supprimer (si `ROLE_MANAGER`)

### Formulaire de création/modification

**Templates:**
- `templates/client/new.html.twig`
- `templates/client/edit.html.twig`

**Champs:**
- Nom (requis)
- Logo (fichier, optionnel)
- Site web (URL, optionnel)
- Description (textarea, optionnel)

**Validation:**
- Nom obligatoire
- Site web: validation URL si renseigné

**Messages flash:**
- Succès: "Client créé avec succès" / "Client modifié avec succès"
- Erreur: (si validation échoue)

### Ajout de contact (`/clients/{id}/contacts/new`)

**Template:** `templates/client_contact/new.html.twig`

**Champs:**
- Prénom (requis)
- Nom (requis)
- Email (optionnel)
- Téléphone fixe (optionnel)
- Téléphone mobile (optionnel)
- Poste / fonction (optionnel)

**Après enregistrement:** Redirection vers la fiche client avec le nouveau contact affiché.

---

## Repository

### ClientRepository

**Fichier:** `src/Repository/ClientRepository.php`

#### Méthode `findAllOrderedByName()`

Retourne tous les clients triés par nom alphabétique.

```php
public function findAllOrderedByName(): array
{
    return $this->createQueryBuilder('c')
        ->orderBy('c.name', 'ASC')
        ->getQuery()
        ->getResult();
}
```

**Usage:**
```php
$clients = $clientRepository->findAllOrderedByName();
```

**Optimisations possibles:**
- Joindre les contacts pour éviter le problème N+1:
  ```php
  ->leftJoin('c.contacts', 'contacts')
  ->addSelect('contacts')
  ```

---

## Cas d'usage

### Cas 1: Créer un nouveau client avec logo

```php
// Dans le contrôleur
$client = new Client();
$client->setName('Acme Corporation');
$client->setWebsite('https://acme.example.com');
$client->setDescription('Client stratégique dans le secteur retail');

// Upload du logo
$logo = $request->files->get('logo');
if ($logo && $logo->isValid()) {
    // Logique d'upload...
    $client->setLogoPath('/uploads/clients/client_xyz.png');
}

$em->persist($client);
$em->flush();

// Redirection vers la fiche
return $this->redirectToRoute('client_show', ['id' => $client->getId()]);
```

### Cas 2: Ajouter plusieurs contacts à un client

```php
$client = $clientRepository->find(42);

// Contact technique
$tech = new ClientContact();
$tech->setClient($client)
    ->setFirstName('Marie')
    ->setLastName('Martin')
    ->setEmail('marie.martin@acme.com')
    ->setPositionTitle('Responsable Technique');

// Contact décisionnel
$decision = new ClientContact();
$decision->setClient($client)
    ->setFirstName('Pierre')
    ->setLastName('Durand')
    ->setEmail('pierre.durand@acme.com')
    ->setPositionTitle('Directeur Marketing');

$em->persist($tech);
$em->persist($decision);
$em->flush();

// Les contacts sont automatiquement ajoutés à $client->getContacts()
```

### Cas 3: Lier un client à un projet

```php
$project = new Project();
$project->setName('Refonte site e-commerce');
$project->setClient($client);  // Relation ManyToOne

$em->persist($project);
$em->flush();

// Retrouver tous les projets d'un client
$projects = $projectRepository->findBy(['client' => $client]);
```

### Cas 4: Archiver un contact (soft delete)

Plutôt que de supprimer un contact, on peut le désactiver:

```php
$contact->setActive(false);
$em->flush();

// Dans les requêtes, filtrer sur active = true
$activeContacts = $clientRepository->createQueryBuilder('c')
    ->leftJoin('c.contacts', 'contact')
    ->andWhere('contact.active = true')
    ->getQuery()
    ->getResult();
```

---

## Évolutions futures

### Fonctionnalités à implémenter

1. **Import/Export**
   - Import CSV de clients
   - Export Excel de la liste des clients
   - Import vCard pour les contacts

2. **Recherche avancée**
   - Recherche full-text sur nom, description
   - Filtres par secteur d'activité
   - Filtres par chiffre d'affaires

3. **Champs additionnels**
   - Secteur d'activité (Enum)
   - Taille de l'entreprise (TPE, PME, ETI, GE)
   - Chiffre d'affaires annuel
   - SIRET / TVA intracommunautaire
   - Adresse postale complète

4. **Relations enrichies**
   - Relation Client ↔ Project (déjà possible via ManyToOne)
   - Historique des interactions (appels, réunions, emails)
   - Pièces jointes (contrats, BDC, etc.)

5. **Gestion des contacts**
   - CRUD complet pour les contacts (actuellement création uniquement)
   - Contacts favoris / principaux
   - Dernière date de contact
   - Notes sur le contact

6. **Tableau de bord client**
   - Vue agrégée: projets, CA, rentabilité
   - Timeline des interactions
   - Prochaines échéances

---

## Bonnes pratiques

### 1. Unicité du nom

Actuellement, rien n'empêche deux clients d'avoir le même nom. Pour éviter les doublons:

```php
// src/Entity/Client.php
#[ORM\Column(type: 'string', length: 180, unique: true)]
private string $name;
```

Ou validation en base:
```php
#[Assert\UniqueEntity(fields: ['name'], message: 'Un client avec ce nom existe déjà')]
```

### 2. Validation des URLs

Ajouter une validation sur le champ `website`:

```php
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Column(type: 'string', length: 255, nullable: true)]
#[Assert\Url(message: 'L\'URL du site web n\'est pas valide')]
private ?string $website = null;
```

### 3. Gestion du logo lors de la suppression

Supprimer physiquement le fichier logo quand le client est supprimé:

```php
// Dans ClientController::delete()
if ($client->getLogoPath()) {
    $fs = new Filesystem();
    $logoFullPath = $this->getParameter('kernel.project_dir').'/public'.$client->getLogoPath();
    if ($fs->exists($logoFullPath)) {
        $fs->remove($logoFullPath);
    }
}
$em->remove($client);
$em->flush();
```

### 4. Utiliser des Form Types

Plutôt que d'hydrater manuellement depuis `$request->request->get()`, utiliser Symfony Forms:

```php
$form = $this->createForm(ClientType::class, $client);
$form->handleRequest($request);

if ($form->isSubmitted() && $form->isValid()) {
    $em->persist($client);
    $em->flush();
    // ...
}
```

**Avantages:**
- Validation automatique
- Protection CSRF intégrée
- Génération HTML simplifiée
- Réutilisabilité

### 5. Pagination

Pour les grandes listes de clients, implémenter la pagination:

```php
use Knp\Component\Pager\PaginatorInterface;

public function index(ClientRepository $repo, PaginatorInterface $paginator, Request $request): Response
{
    $query = $repo->createQueryBuilder('c')
        ->orderBy('c.name', 'ASC')
        ->getQuery();

    $clients = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        20  // Items par page
    );

    return $this->render('client/index.html.twig', ['clients' => $clients]);
}
```

---

## Voir aussi

- [Entités](entities.md) - Descriptions détaillées des entités
- [Rôles et Permissions](roles.md) - Hiérarchie des rôles
- [Good Practices](good-practices.md) - Conventions de code
- [Features](features.md) - Vue d'ensemble des fonctionnalités
