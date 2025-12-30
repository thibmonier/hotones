# Arborescence du projet

## les entrées de niveau 1

- Commerce (contient les devis et des dashboards de suivi financier comme les volumes de CA des devis sur une période en fonction de leurs status)
- Delivery
- Comptabilité
- Administration
- Configuration 
- Analytics

## les entrées de niveau 2

| niveau 1       | niveau 2              | description                                 | Role d'accès               | Commentaires                                                           |
|----------------|-----------------------|---------------------------------------------|----------------------------|------------------------------------------------------------------------|
| Commerce       | Clients               | lien vers les clients                       | Commercial, Chef de projet |                                                                        |
| Commerce       | Nouveau client        | lien vers l'ajout d'un client               | Commercial, Chef de projet |                                                                        |
| Commerce       | Devis                 | lien vers les devis                         | Commercial, Chef de projet |                                                                        |
| Commerce       | Nouveau devis         | lien vers l'ajout d'un devis                | Commercial, Chef de projet |                                                                        |                 
| Commerce       | KPI commerce          | lien vers le dashboard commercial           | Commercial, Chef de projet | ✅ Dashboard de suivi des KPIs commerciaux (devis, CA, évolution)       |
| Delivery       | Projets               | lien vers les projets                       | Commercial, Chef de projet |                                                                        |
| Delivery       | Nouveau Projet        | lien vers l'ajout d'un projet               | Commercial, Chef de projet |                                                                        |
| Delivery       | Planning              | Lien vers le planning de l'agence           | Commercial, Chef de projet | cet écran n'est pas encore finalisé, il faut l'inclure dans la roadmap |
| Delivery       | Mes tâches            | lien vers mes tâches                        | Contributeur               | cet écran est à refondre                                               |
| Delivery       | Saisir mes temps      | lien vers saisir mes temps                  | Contributeur               | cet écran est à refondre                                               |
| Delivery       | Mon historique        | lien vers mon historique                    | Contributeur               | cet écran est à refondre                                               |
| Delivery       | Tous les temps        | lien vers tous les temps                    | Delivery                   | cet écran est à refondre                                               |
| Comptabilité   | Facturation           | Lien vers le tableau de facturation         | Comptabilité               |                                                                        |
| Administration | Contributeurs         | lien vers les contributeurs                 | Comptabilité, Delivery     |                                                                        |
| Administration | Nouveau contributeur  | lien vers l'ajout d'un contributeur         | Comptabilité, Delivery     |                                                                        |
| Administration | Utilisateurs et rôles | lien vers la gestion des utilisateurs       | Comptabilité, Delivery     |                                                                        |
| Administration | Périodes d'emploi     | Lien vers les périodes d'emploi             | Comptabilité, Delivery     | l'ajout de périodes d'emploi doit se faire dans l'écran                |
| Configuration  | Technologies          | lien vers l'administration des technologies | Administrateur, Delivery   |                                                                        |
| Configuration  | Catégories de service | lien vers les catégories de service         | Administrateur, Delivery   |                                                                        |
| Configuration  | Profils métier        | lien vers les profils métiers               | Administrateur, Delivery   |                                                                        |
| Configuration  | Paramètres généraux   | lien vers les paramètres généraux           | Administrateur, Delivery   |                                                                        |
| Configuration  | Scheduler             | lien vers le scheduler                      | Administrateur             |                                                                        |
| Configuration  | Notifications         | lien vers les paramétrages de notifications | Administrateur, Delivery   |                                                                        |
| Analytics      | Dashboard KPIs        |                                             | Administrateur, Delivery   |                                                                        |
| Analytics      | Staffing & TACE       |                                             | Delivery                   | écran à finaliser (à mettre à jour dans la roadmap)                    |
