# 🗄️ Connexion à la base de données HotOnes

## 📋 Configuration de connexion

### Paramètres principaux
```
Host: localhost (ou 127.0.0.1)
Port: 3307 ⚠️ (pas 3306 !)
Database: hotones
Username: symfony
Password: symfony
Type: MySQL/MariaDB 11.4
```

### Utilisateur root (admin)
```
Username: root
Password: root
```

---

## ⚙️ Configuration par client

### 🚀 **PhpStorm / IntelliJ IDEA**
1. **Database Tool** : `View > Tool Windows > Database`
2. **Nouvelle source** : `+` > `Data Source` > `MySQL`
3. **Paramètres** :
   - Host: `localhost`
   - Port: `3307`
   - Database: `hotones`
   - User: `symfony`
   - Password: `symfony`
4. **Tester** : `Test Connection`
5. **Driver** : MySQL (télécharger si demandé)

### 🐘 **DBeaver**
1. **Nouvelle connexion** : `Database > New Database Connection`
2. **Type** : `MySQL`
3. **Paramètres** :
   - Server Host: `localhost`
   - Port: `3307`
   - Database: `hotones`
   - Username: `symfony`
   - Password: `symfony`
4. **Test Connection**

### 🔧 **MySQL Workbench**
1. **Nouvelle connexion** : `+` à côté de `MySQL Connections`
2. **Paramètres** :
   - Connection Name: `HotOnes Local`
   - Hostname: `127.0.0.1`
   - Port: `3307`
   - Username: `symfony`
   - Password: `symfony` (Store in Vault)

### 🍎 **Sequel Pro/Sequel Ace (macOS)**
```
Host: 127.0.0.1
Username: symfony
Password: symfony
Database: hotones
Port: 3307
```

---

## 🛠️ Dépannage

### ❌ **Problèmes courants**

**1. "Connection refused" ou "Cannot connect"**
```bash
# Vérifier que Docker est lancé
docker compose ps

# Si containers arrêtés, les démarrer
docker compose up -d

# Vérifier le port
nc -z localhost 3307
```

**2. "Access denied for user"**
- Vérifiez les identifiants : `symfony` / `symfony`
- Ou utilisez root : `root` / `root`

**3. "Database 'hotones' doesn't exist"**
```bash
# Vérifier les bases disponibles
docker compose exec db mariadb -u root -proot -e "SHOW DATABASES;"

# Recréer la base si nécessaire
docker compose exec app php bin/console doctrine:database:create
```

**4. "Tables not found"**
```bash
# Lancer les migrations
docker compose exec app php bin/console doctrine:migrations:migrate

# Ou vérifier les tables existantes
docker compose exec db mariadb -u symfony -psymfony hotones -e "SHOW TABLES;"
```

### ✅ **Commandes de vérification**
```bash
# Status des containers
docker compose ps

# Logs de la base de données
docker compose logs db

# Test de connexion depuis l'intérieur
docker compose exec db mariadb -u symfony -psymfony -e "SELECT VERSION();"

# Test du port depuis l'host
nc -z localhost 3307 && echo "Port accessible" || echo "Port inaccessible"
```

---

## 📊 **Informations sur la base**

### Tables principales
```
- users                 : Comptes utilisateurs
- contributors          : Intervenants 
- employment_periods    : Historique RH
- projects              : Projets clients
- orders                : Devis
- timesheets           : Saisie des temps
- planning             : Planification
- technologies         : Stack technique
- service_categories   : Types de services
- profiles             : Profils métier
```

### Tables analytics (modèle en étoile)
```
- dim_time             : Dimension temporelle
- dim_project_type     : Dimension types projet  
- dim_contributor      : Dimension contributeurs
- fact_project_metrics : Table de faits (KPIs)
```

---

## 🔐 **Sécurité**

⚠️ **Attention** : Cette configuration est pour le développement local uniquement.
- Les mots de passe sont simples (`symfony`/`root`)
- Le port 3307 est exposé publiquement
- Ne jamais utiliser en production

Pour la production, utiliser :
- Mots de passe complexes
- Connexions SSL
- Accès restreint par IP
- Variables d'environnement sécurisées