# Sprint 1 — Guide des tests

Ce document décrit les tests à écrire pour valider les fonctionnalités du Sprint 1.

---

## Structure des tests

```
tests/
├── Unit/
│   ├── Entity/
│   │   ├── UserTest.php
│   │   ├── ContributorTest.php
│   │   └── EmploymentPeriodTest.php
│   └── Service/
│       └── (si services métier créés)
├── Functional/
│   ├── Controller/
│   │   ├── ContributorControllerTest.php
│   │   ├── EmploymentPeriodControllerTest.php
│   │   └── ProfileControllerTest.php
│   └── Form/
│       ├── ContributorTypeTest.php
│       └── EmploymentPeriodTypeTest.php
└── E2E/ (optionnel, avec Panther)
    ├── ContributorCrudTest.php
    └── ProfileManagementTest.php
```

---

## Tests unitaires

### UserTest.php

Tests des méthodes de l'entité User :

```php
<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testGetFullName(): void
    {
        $user = new User();
        $user->setFirstName('John');
        $user->setLastName('Doe');
        
        $this->assertEquals('John Doe', $user->getFullName());
    }
    
    public function testHasRole(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_CHEF_PROJET']);
        
        $this->assertTrue($user->hasRole('ROLE_CHEF_PROJET'));
        $this->assertTrue($user->hasRole('ROLE_USER')); // inherited
        $this->assertFalse($user->hasRole('ROLE_ADMIN'));
    }
    
    public function testIsChefProjet(): void
    {
        $user = new User();
        $user->setRoles(['ROLE_CHEF_PROJET']);
        
        $this->assertTrue($user->isChefProjet());
        $this->assertFalse($user->isSuperAdmin());
    }
    
    public function testTotpAuthenticationEnabled(): void
    {
        $user = new User();
        $this->assertFalse($user->isTotpAuthenticationEnabled());
        
        $user->setTotpSecret('ABC123');
        $user->setTotpEnabled(true);
        $this->assertTrue($user->isTotpAuthenticationEnabled());
    }
}
```

### EmploymentPeriodTest.php

Tests du calcul automatique du CJM :

```php
<?php

namespace App\Tests\Unit\Entity;

use App\Entity\EmploymentPeriod;
use PHPUnit\Framework\TestCase;

class EmploymentPeriodTest extends TestCase
{
    public function testCjmCalculation(): void
    {
        $period = new EmploymentPeriod();
        $period->setSalary('3000'); // 3000€/mois
        $period->setWeeklyHours(35);
        $period->setWorkTimePercentage(100);
        
        // Calcul attendu: 3000 / 21 (jours ouvrés moyens) ≈ 142.86€
        // À adapter selon la logique métier exacte
        $this->assertNotNull($period->getCjm());
        $this->assertGreaterThan(0, (float)$period->getCjm());
    }
}
```

---

## Tests fonctionnels

### ContributorControllerTest.php

Tests CRUD des contributeurs :

```php
<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Contributor;
use App\Repository\ContributorRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ContributorControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private ContributorRepository $repository;
    
    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->repository = static::getContainer()->get(ContributorRepository::class);
        
        // Connexion avec un utilisateur ROLE_MANAGER
        $this->loginAsManager();
    }
    
    public function testIndex(): void
    {
        $this->client->request('GET', '/contributors');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Contributeurs');
    }
    
    public function testNewContributor(): void
    {
        $this->client->request('GET', '/contributors/new');
        $this->assertResponseIsSuccessful();
        
        $this->client->submitForm('Enregistrer', [
            'contributor[firstName]' => 'Jane',
            'contributor[lastName]' => 'Smith',
            'contributor[email]' => 'jane.smith@example.com',
            'contributor[active]' => true,
        ]);
        
        $this->assertResponseRedirects();
        $this->client->followRedirect();
        
        $contributor = $this->repository->findOneBy(['email' => 'jane.smith@example.com']);
        $this->assertNotNull($contributor);
        $this->assertEquals('Jane', $contributor->getFirstName());
    }
    
    public function testUploadAvatar(): void
    {
        // Créer un fichier de test
        $avatarPath = tempnam(sys_get_temp_dir(), 'avatar');
        copy(__DIR__ . '/../../fixtures/test-avatar.jpg', $avatarPath);
        $avatar = new UploadedFile($avatarPath, 'avatar.jpg', 'image/jpeg', null, true);
        
        $this->client->request('GET', '/contributors/new');
        
        $this->client->submitForm('Enregistrer', [
            'contributor[firstName]' => 'Test',
            'contributor[lastName]' => 'Avatar',
            'contributor[email]' => 'test.avatar@example.com',
            'contributor[avatarFile]' => $avatar,
        ]);
        
        $contributor = $this->repository->findOneBy(['email' => 'test.avatar@example.com']);
        $this->assertNotNull($contributor->getAvatarFilename());
        $this->assertFileExists(
            __DIR__ . '/../../public/uploads/avatars/' . $contributor->getAvatarFilename()
        );
    }
    
    private function loginAsManager(): void
    {
        // Implémenter la connexion avec un utilisateur test
        // Voir documentation Symfony Testing
    }
}
```

### ProfileControllerTest.php

Tests du profil utilisateur :

```php
<?php

namespace App\Tests\Functional\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProfileControllerTest extends WebTestCase
{
    public function testProfilePage(): void
    {
        $client = static::createClient();
        $this->loginAsUser($client);
        
        $client->request('GET', '/me');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorExists('.page-title-box');
        $this->assertSelectorTextContains('h4', 'Mon profil');
    }
    
    public function testEditProfile(): void
    {
        $client = static::createClient();
        $user = $this->loginAsUser($client);
        
        $client->request('GET', '/me/edit');
        $this->assertResponseIsSuccessful();
        
        $client->submitForm('Enregistrer', [
            'first_name' => 'UpdatedFirstName',
            'last_name' => 'UpdatedLastName',
            'phone_work' => '0123456789',
        ]);
        
        $this->assertResponseRedirects('/me');
        
        // Vérifier en base
        $em = static::getContainer()->get('doctrine')->getManager();
        $updatedUser = $em->getRepository(\App\Entity\User::class)->find($user->getId());
        
        $this->assertEquals('UpdatedFirstName', $updatedUser->getFirstName());
        $this->assertEquals('0123456789', $updatedUser->getPhoneWork());
    }
    
    public function testChangePassword(): void
    {
        $client = static::createClient();
        $user = $this->loginAsUser($client);
        
        $client->request('GET', '/me/password');
        $this->assertResponseIsSuccessful();
        
        $client->submitForm('Changer le mot de passe', [
            'current_password' => 'oldpassword',
            'new_password' => 'NewSecureP@ssw0rd',
            'confirm_password' => 'NewSecureP@ssw0rd',
        ]);
        
        $this->assertResponseRedirects('/me');
        $this->assertSelectorTextContains('.alert-success', 'Mot de passe mis à jour');
    }
    
    private function loginAsUser($client)
    {
        // Créer ou récupérer un utilisateur test
        // Retourner l'instance User
    }
}
```

---

## Commandes de test

### Lancer tous les tests
```bash
php bin/phpunit
```

### Lancer les tests unitaires uniquement
```bash
php bin/phpunit tests/Unit
```

### Lancer les tests fonctionnels uniquement
```bash
php bin/phpunit tests/Functional
```

### Lancer un test spécifique
```bash
php bin/phpunit tests/Functional/Controller/ProfileControllerTest.php
```

### Coverage (si xdebug installé)
```bash
XDEBUG_MODE=coverage php bin/phpunit --coverage-html coverage
```

---

## Fixtures de test

Créer des fixtures pour les tests :

**tests/fixtures/test-avatar.jpg**
- Image de test 100x100px

**tests/DataFixtures/TestFixtures.php** (si nécessaire)
```php
<?php

namespace App\Tests\DataFixtures;

use App\Entity\User;
use App\Entity\Contributor;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class TestFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $hasher)
    {
    }
    
    public function load(ObjectManager $manager): void
    {
        // Créer un utilisateur test
        $user = new User();
        $user->setEmail('test@example.com');
        $user->setFirstName('Test');
        $user->setLastName('User');
        $user->setPassword($this->hasher->hashPassword($user, 'password'));
        $user->setRoles(['ROLE_MANAGER']);
        
        $manager->persist($user);
        
        // Créer un contributeur test
        $contributor = new Contributor();
        $contributor->setFirstName('John');
        $contributor->setLastName('Doe');
        $contributor->setEmail('john.doe@example.com');
        $contributor->setActive(true);
        $contributor->setUser($user);
        
        $manager->persist($contributor);
        
        $manager->flush();
    }
}
```

---

## Configuration PHPUnit

**phpunit.xml.dist**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Functional">
            <directory>tests/Functional</directory>
        </testsuite>
    </testsuites>
    
    <coverage>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>src/DataFixtures</directory>
            <file>src/Kernel.php</file>
        </exclude>
    </coverage>
    
    <php>
        <env name="APP_ENV" value="test"/>
        <env name="KERNEL_CLASS" value="App\Kernel"/>
        <env name="DATABASE_URL" value="sqlite:///:memory:"/>
    </php>
</phpunit>
```

---

## Checklist

- [ ] Installer PHPUnit si nécessaire : `composer require --dev phpunit/phpunit`
- [ ] Créer les fichiers de test unitaires
- [ ] Créer les fichiers de test fonctionnels
- [ ] Créer les fixtures de test (images, données)
- [ ] Configurer la base de données de test
- [ ] Lancer les tests et corriger les erreurs
- [ ] Vérifier la couverture de code (objectif > 80%)
- [ ] Intégrer les tests dans la CI/CD

---

## Ressources

- [Documentation Symfony Testing](https://symfony.com/doc/current/testing.html)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Symfony Best Practices - Testing](https://symfony.com/doc/current/best_practices.html#tests)
