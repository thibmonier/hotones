# Évolutions en cours

Ce fichier contient les idées d'évolution en cours de réflexion ou en discussion.

Une fois validées et priorisées, ces évolutions sont déplacées dans [ROADMAP.md](./ROADMAP.md).

---

## compléments pour la mise en place des tenants

Lors de la mise en place des tenants pour avoir des comptes sociétés différents, il faudrait :
- que chaque société puisse utiliser une base de données séparée pour ses propres données pour rester cloisonnée avec les autres
- que chaque module puisse être débrayable via de la configuration (par exemple, je ne veux pas la facturation)
- Evaluer l'impact, la faisabilité et l'intégration dans le sprint associé. Si la mise en place est trop complexe, répartir en étape sur plusieurs sprints/phases
- Lors du passage en tenant, il faudrait que chaque client puisse configurer sa propre clé d'IA parmi les connecteurs en place (Anthropic, OpenAI, Gemini). Si plusieurs clés sont actives, il faudrait que l'administrateur du tenant puisse choisir la clé à privilégier

---
## refonte des pages publiques.

- revoir les pages publiques pour mettre en scène Unit404 dans les pages à partir des différentes vues qu'on a du personnage.
- Passer le thème en light plutôt qu'en dark avec une option pour basculer et que ce soit automatique en fonction du paramétrage de l'utilisateur.
- revoir les textes pour matcher avec le thème de l'application sans rentrer dans les sujets de conception techniques
- mettre en place un petit bandeau spécifiant que le projet est en cours de lancement mais pas encore actif
- mettre les vraies images
- ajouter une entrée "intégrateurs" qui permettrait d'accéder à la documentation de l'api
- indiquer que l'achitecture cloud du service est hébergée intégralement en Europe (RGPD compliant)

### revoir la page pricing

revoir la page de princing avec l'ensemble de ces nouveaux éléments : 

Hotones est un ERP métier pour agences digitales avec IA embarquée. 

_Avec un outil all-in-one qui remplace plusieurs solutions (CRM commercial + gestion de projet + compta analytique + RH + IA), tu peux justifier un pricing bien plus élevé :_

Benchmark des solutions actuelles pour agences
Une agence utilise typiquement :

CRM/Commercial : Salesforce (50-150€/user) ou HubSpot (45-120€/user)
Gestion de projet : Monday.com (10-20€/user), Asana (13-30€/user)
Time tracking/rentabilité : Harvest (12€/user), Toggl (10-20€/user)
Finance/tréso : Pennylane (30-60€/mois) + expert comptable
RH : Lucca (5-15€/user)

Coût total moyen : 80-200€/utilisateur/mois selon la stack complète
Pricing recommandé pour Hotones (modèle par lots)

| Formule | Users | Prix/mois | Modules inclus | Cible | 
| Starter | 5-15 users | 299€ | Commercial + Projets + Facturation (IA limitée) | TPE/freelances structurés | 
| Business | 16-50 users | 699€ | Tous modules + IA complète + Intégrations | PME, agences 20-40 personnes | 
| Enterprise | 51-150 users | 1 299€ | Tous modules + IA avancée + Support dédié + Custom | Grosses agences |

### contenu des pages de features (publiques)

les features doivent montrer :
- l'utilisation de l'IA dans le quotidien et dans chaque outil
- la conformité RGPD
- l'anticipation de conformité avec la loi sur la facturation electronique
- les connecteurs avec les outils principaux du marché pour la gestion de projet, le CRM, les outils comptables.
- le chatbot IA qui aide dans les tâches courantes

### revoir le design des pages publiques

le design du site public doit évoluer ou repartir de 0.
Il doit etre à l'image de Unit404, c'est à dire en version light, utilisant l'image de Unit404 comme mascotte un peu partout


## revoir la cohérence du parcours

pour chaque utilisateur, vérifier les pages présentes et utilisables en fonction de ses droits.

## evolutions des collaborateurs/utilisateurs

pour pouvoir vérifier l'ensemble des droits utilisateurs, il faudrait une commande pour créer à minima un utilisateur par profil/role
il faudrait pouvoir fusionner les formulaires utilisateurs et collaborateurs. 

## finaliser la mise en place du backoffice

revoir l'accès au lien (seuls les administrateurs ont accès et ils devraient pouvoir y accéder depuis le header et non pas dasn le menu latéral)
revoir les éléments présents dans le backoffice et ceux présents dans l'application

Il faudrait pouvoir administrer les sociétés clientes depuis le backoffice (CRUD company)

Permettre le lancement depuis l'écran du backoffice des taches du scheduler (sans attendre la configuration du cron)

## cohérence ux

revoir les pages existantes pour ne conserver que les pages utiles

## prévoir l'intégration d'un PSP pour le paiement de l'abonnement 


## qualité technique

revoir la séparation MVC notamment l'absence de requetes dans les controlleurs (mais déportées dans les repositories)

nettoyage et rangement de l'ensemble des templates (ils commencent à être nombreux)

finalisation des optimisations php8.5 (SPRINT-PHP85-OPTIMIZATIONS.md)
prise en compte des remarques doctrine_doctor (et profiling blackfire-profiling.md)

voir si il n'y a pas de la factorisation possible pour limiter le code dupliquer en mettant en place des traits par exemple ou en factorisant simplement

## ecran de suivi

Je voudrais qu'il soit possible d'avoir un écran de suivi visible sur une télé dans les bureaux de la société.
Cet écran montrerait des chiffres actualisés de l'agence, le nombre de projets actifs, les alertes et échéances communes (saisie des temps par exemple)



---



Toutes les évolutions ont été intégrées dans la roadmap. Consultez [ROADMAP.md](./ROADMAP.md) pour voir la planification complète.
