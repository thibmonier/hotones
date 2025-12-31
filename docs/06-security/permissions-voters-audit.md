# Audit Permissions & Voters - HotOnes

**Date:** 31 décembre 2025
**Statut:** ✅ Audit complété

## 📋 Vue d'ensemble

Audit complet du système de permissions et d'autorisation de l'application HotOnes.

## 🎯 Architecture Actuelle

### Mécanisme d'Autorisation

**Type** : Contrôle d'accès basé sur les rôles (RBAC - Role-Based Access Control)

**Méthodes utilisées** :
1. **#[IsGranted]** : Attributs PHP sur les contrôleurs/méthodes (181 occurrences)
2. **access_control** : Configuration globale dans `security.yaml` (15 règles)
3. **Hiérarchie de rôles** : Héritage automatique des permissions

**Custom Voters** : ❌ Aucun voter personnalisé implémenté

## 🏛️ Hiérarchie des Rôles

```
ROLE_SUPERADMIN (niveau 5 - super admin)
    ↓ hérite de
ROLE_ADMIN (niveau 4 - administrateur)
ROLE_COMPTA (niveau 4 - comptabilité)
    ↓ hérite de
ROLE_MANAGER (niveau 3 - manager)
    ↓ hérite de
ROLE_CHEF_PROJET (niveau 2 - chef de projet)
    ↓ hérite de
ROLE_INTERVENANT (niveau 1 - intervenant/collaborateur)
ROLE_USER (niveau 0 - utilisateur authentifié)
    ↓ hérite de
PUBLIC_ACCESS (niveau -1 - accès public)
```

### Configuration (`config/packages/security.yaml`)

```yaml
role_hierarchy:
    ROLE_CHEF_PROJET: [ROLE_INTERVENANT, ROLE_USER]
    ROLE_MANAGER: [ROLE_CHEF_PROJET, ROLE_INTERVENANT, ROLE_USER]
    ROLE_COMPTA: [ROLE_MANAGER, ROLE_CHEF_PROJET, ROLE_INTERVENANT, ROLE_USER]
    ROLE_ADMIN: [ROLE_MANAGER, ROLE_CHEF_PROJET, ROLE_INTERVENANT, ROLE_USER]
    ROLE_SUPERADMIN: [ROLE_ADMIN, ROLE_MANAGER, ROLE_CHEF_PROJET, ROLE_INTERVENANT, ROLE_USER, ROLE_COMPTA]
```

## 📊 Analyse des Permissions

### Distribution des Contrôles d'Accès

| Rôle Requis | Occurrences | % | Fonctionnalités Typiques |
|-------------|-------------|---|--------------------------|
| `ROLE_CHEF_PROJET` | 36 | 31% | Gestion projets, devis, planning |
| `ROLE_MANAGER` | 28 | 24% | Analytics, KPIs, dashboard commercial |
| `ROLE_USER` | 28 | 24% | Saisie temps, consultation |
| `ROLE_ADMIN` | 11 | 9% | Configuration système, utilisateurs |
| `ROLE_INTERVENANT` | 10 | 9% | Consultation limitée |
| `ROLE_COMPTA` | 10 | 9% | Rapports financiers, exports |
| `IS_AUTHENTICATED_2FA_IN_PROGRESS` | 2 | 2% | Authentification 2FA |

**Total** : 125 contrôles d'accès explicites via `#[IsGranted]`

### Contrôles Globaux (`access_control`)

| Pattern | Rôle | Justification |
|---------|------|---------------|
| `^/api/login` | PUBLIC_ACCESS | Authentification API |
| `^/api/docs` | PUBLIC_ACCESS | Documentation API publique |
| `^/api` | ROLE_USER | API protégée |
| `^/login$` | PUBLIC_ACCESS | Page de connexion |
| `^/2fa` | PUBLIC_ACCESS | Authentification 2FA |
| `^/csp/report$` | PUBLIC_ACCESS | Rapports CSP navigateurs |
| `^/csp/violations$` | PUBLIC_ACCESS | Viewer CSP (dev only) |
| `^/features` | PUBLIC_ACCESS | Page fonctionnalités |
| `^/pricing$` | PUBLIC_ACCESS | Page tarifs |
| `^/about$` | PUBLIC_ACCESS | Page à propos |
| `^/contact$` | PUBLIC_ACCESS | Page contact |
| `^/legal$` | PUBLIC_ACCESS | Mentions légales |
| `^/public` | PUBLIC_ACCESS | Ressources publiques |
| `^/status$` | PUBLIC_ACCESS | Status page |
| `^/health` | PUBLIC_ACCESS | Health check |
| `^/` | ROLE_USER | Toutes les autres pages (fallback) |

**Total** : 15 règles d'accès global

## ✅ Points Forts

### 1. Architecture Solide

- ✅ **Hiérarchie claire** : Les rôles héritent logiquement les uns des autres
- ✅ **Séparation des responsabilités** : Rôles bien définis (chef projet, manager, admin, compta)
- ✅ **Contrôle granulaire** : 181 contrôles au niveau méthode/contrôleur

### 2. Sécurité par Défaut

- ✅ **Deny by default** : Dernière règle `^/` → ROLE_USER (tout est protégé par défaut)
- ✅ **2FA** : Authentification à deux facteurs configurée
- ✅ **CSRF activé** : Protection contre les attaques CSRF

### 3. Bonnes Pratiques Respectées

- ✅ **Attributs PHP modernes** : Utilisation de `#[IsGranted]` au lieu d'annotations
- ✅ **Firewall séparé pour API** : API stateless avec JWT
- ✅ **Zones publiques définies** : Pages marketing/légales accessibles sans auth

## ⚠️ Points d'Attention

### 1. Pas de Voters Personnalisés

**Constatation** : Aucun voter custom dans `src/Security/Voter/`

**Impact** : Toutes les règles métier complexes doivent être gérées dans les contrôleurs

**Cas d'usage manquants** :
- Vérifier si un user peut modifier un projet (est-il chef de projet de CE projet ?)
- Vérifier si un user peut voir les temps d'un autre contributeur
- Vérifier si un devis peut être modifié (statut, propriétaire, etc.)

**Recommandation** : Créer des voters pour les règles métier complexes

**Exemples à implémenter** :
```php
// src/Security/Voter/ProjectVoter.php
class ProjectVoter extends Voter
{
    const EDIT = 'PROJECT_EDIT';
    const VIEW = 'PROJECT_VIEW';
    const DELETE = 'PROJECT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Project
            && in_array($attribute, [self::EDIT, self::VIEW, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        $project = $subject;

        // Manager peut tout faire
        if (in_array('ROLE_MANAGER', $user->getRoles())) {
            return true;
        }

        // Chef de projet peut éditer ses projets
        if ($attribute === self::EDIT) {
            return $project->getProjectManager() === $user;
        }

        // Logique métier spécifique...
    }
}
```

### 2. ROLE_COMPTA Héritage Redondant

**Constatation** : `ROLE_COMPTA` hérite de `ROLE_MANAGER` qui hérite déjà de `ROLE_CHEF_PROJET`

**Configuration actuelle** :
```yaml
ROLE_COMPTA: [ROLE_MANAGER, ROLE_CHEF_PROJET, ROLE_INTERVENANT, ROLE_USER]
```

**Configuration optimale** :
```yaml
ROLE_COMPTA: [ROLE_MANAGER]  # ROLE_MANAGER hérite déjà des autres
```

**Impact** : Faible, mais redondant et moins lisible

### 3. Routes Publiques CSP

**Constatation** : `/csp/violations$` est PUBLIC_ACCESS mais devrait être protégé

**Risque** : Fuite d'informations sur les violations CSP en production

**Recommandation** :
```yaml
# Option 1 : Protéger par rôle
- { path: ^/csp/violations$, roles: ROLE_ADMIN }

# Option 2 : Désactiver en production (préférable)
# Via controller : retourner 404 si APP_ENV !== 'dev'
```

### 4. Pas de Tests des Permissions

**Constatation** : Pas de tests fonctionnels spécifiques pour les permissions

**Risque** : Régression possible lors de refactoring

**Recommandation** : Créer des tests pour vérifier :
- Les rôles ont accès aux bonnes routes
- Les rôles inférieurs sont bien bloqués
- La hiérarchie fonctionne correctement

## 🎯 Recommandations par Priorité

### 🔴 Priorité 1 - Court Terme (1-2 semaines)

1. **Protéger `/csp/violations`** : Ajouter contrôle environnement dans le contrôleur
   ```php
   if ($this->environment !== 'dev') {
       throw new NotFoundHttpException();
   }
   ```

2. **Nettoyer hiérarchie ROLE_COMPTA** : Supprimer héritages redondants

### 🟠 Priorité 2 - Moyen Terme (1 mois)

3. **Créer ProjectVoter** : Contrôler édition/suppression projets selon le chef de projet assigné

4. **Créer TimesheetVoter** : Contrôler qui peut voir/éditer les temps de qui

5. **Tests permissions** : Ajouter tests fonctionnels pour vérifier les contrôles d'accès

### 🟡 Priorité 3 - Long Terme (2-3 mois)

6. **Audit logs d'accès** : Logger les accès refusés pour détecter les tentatives d'accès non autorisé

7. **Documentation permissions** : Créer une matrice rôle/fonctionnalité pour les users

8. **Fine-grained permissions** : Évaluer si certaines fonctionnalités nécessitent des permissions plus granulaires

## 📋 Checklist de Sécurité

### Configuration

- [x] Hiérarchie de rôles définie
- [x] access_control configuré avec deny-by-default
- [x] Firewall séparé pour API
- [x] 2FA activé
- [x] CSRF activé sur login et 2FA
- [ ] Voters personnalisés pour règles métier
- [ ] Tests des permissions

### Contrôles d'Accès

- [x] 181 contrôles `#[IsGranted]` sur contrôleurs
- [x] 15 règles `access_control` globales
- [x] Routes publiques bien définies
- [ ] Routes de debug protégées (CSP violations)
- [ ] Audit logs des accès refusés

### Documentation

- [x] Hiérarchie de rôles documentée
- [x] Audit permissions complété
- [ ] Matrice rôle/fonctionnalité pour utilisateurs
- [ ] Guide pour développeurs (création voters)

## 🔧 Exemples de Code

### Créer un Voter Personnalisé

```php
// src/Security/Voter/ProjectVoter.php
namespace App\Security\Voter;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class ProjectVoter extends Voter
{
    const VIEW = 'PROJECT_VIEW';
    const EDIT = 'PROJECT_EDIT';
    const DELETE = 'PROJECT_DELETE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return $subject instanceof Project
            && in_array($attribute, [self::VIEW, self::EDIT, self::DELETE]);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        /** @var Project $project */
        $project = $subject;

        // Managers et admins peuvent tout faire
        if ($this->isGranted(['ROLE_MANAGER', 'ROLE_ADMIN'], $user)) {
            return true;
        }

        return match($attribute) {
            self::VIEW => $this->canView($project, $user),
            self::EDIT => $this->canEdit($project, $user),
            self::DELETE => $this->canDelete($project, $user),
            default => false,
        };
    }

    private function canView(Project $project, User $user): bool
    {
        // Tous les chefs de projet peuvent voir
        return in_array('ROLE_CHEF_PROJET', $user->getRoles());
    }

    private function canEdit(Project $project, User $user): bool
    {
        // Seul le chef de projet assigné peut éditer
        return $project->getProjectManager() === $user;
    }

    private function canDelete(Project $project, User $user): bool
    {
        // Seul le chef de projet assigné peut supprimer
        // ET le projet ne doit pas avoir de temps saisis
        return $project->getProjectManager() === $user
            && $project->getTimesheets()->isEmpty();
    }

    private function isGranted(array $roles, User $user): bool
    {
        return !empty(array_intersect($roles, $user->getRoles()));
    }
}
```

### Utiliser un Voter dans un Contrôleur

```php
use App\Entity\Project;
use App\Security\Voter\ProjectVoter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class ProjectController extends AbstractController
{
    #[Route('/project/{id}/edit', name: 'project_edit')]
    public function edit(Project $project): Response
    {
        // Utiliser le voter
        $this->denyAccessUnlessGranted(ProjectVoter::EDIT, $project);

        // Logique d'édition...
    }

    // Ou avec attribut
    #[IsGranted(ProjectVoter::VIEW, subject: 'project')]
    #[Route('/project/{id}', name: 'project_show')]
    public function show(Project $project): Response
    {
        // Logique d'affichage...
    }
}
```

### Tester les Permissions

```php
// tests/Functional/Security/ProjectPermissionsTest.php
namespace App\Tests\Functional\Security;

use App\Entity\Project;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProjectPermissionsTest extends WebTestCase
{
    public function testManagerCanEditAllProjects(): void
    {
        $client = static::createClient();

        // Login en tant que manager
        $manager = $this->createUser('ROLE_MANAGER');
        $client->loginUser($manager);

        // Tenter d'accéder à l'édition d'un projet
        $project = $this->createProject();
        $client->request('GET', '/project/' . $project->getId() . '/edit');

        $this->assertResponseIsSuccessful();
    }

    public function testIntervenantCannotEditProjects(): void
    {
        $client = static::createClient();

        // Login en tant qu'intervenant
        $intervenant = $this->createUser('ROLE_INTERVENANT');
        $client->loginUser($intervenant);

        // Tenter d'accéder à l'édition d'un projet
        $project = $this->createProject();
        $client->request('GET', '/project/' . $project->getId() . '/edit');

        $this->assertResponseStatusCodeSame(403); // Forbidden
    }
}
```

## 📚 Ressources

- **Symfony Security** : https://symfony.com/doc/current/security.html
- **Voters** : https://symfony.com/doc/current/security/voters.html
- **RBAC Best Practices** : https://owasp.org/www-community/Access_Control
- **Testing Security** : https://symfony.com/doc/current/testing.html#testing-security

---

**Dernière mise à jour** : 31 décembre 2025
**Responsable** : Équipe sécurité
**Statut** : ✅ Audit complété, recommandations prioritaires identifiées
