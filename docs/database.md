# 📊 Connexion à la base de données

## Paramètres clients externes (PhpStorm, DBeaver, etc.)
```
Host: localhost (ou 127.0.0.1)
Port: 3307 (pas 3306)
Database: hotones
Username: symfony
Password: symfony
Type: MySQL/MariaDB 11.4
```

## Utilisateur root
```
Username: root
Password: root
```

## Commandes de vérification
```bash
docker compose ps
nc -z localhost 3307
docker compose exec db mariadb -u symfony -psymfony hotones
```

📝 Guide complet : voir `DATABASE-CONNECTION.md`.
