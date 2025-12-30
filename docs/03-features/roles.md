# Rôles de l'application

| rôle             | hierarchie (parent) | description                           |
|------------------|---------------------|---------------------------------------|
| ROLE_USER        | ROLE_CHEF_PROJET    | Utilisateur connecté                  |
| ROLE_INTERVENANT | ROLE_CHEF_PROJET    | Contributeur                          |
| ROLE_CHEF_PROJET | ROLE_MANAGER        | Chef de projet, Commercial            |
| ROLE_MANAGER     | ROLE_COMPTA         | Manager                               |
| ROLE_COMPTA      | ROLE_ADMIN          | Comptabilité                          |
| ROLE_ADMIN       | ROLE_SUPERADMIN     | Delivery, Direction                   |
| ROLE_SUPERADMIN  |                     | Super administrateur de l'application |

