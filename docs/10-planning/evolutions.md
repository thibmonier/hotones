# Évolutions en cours

Ce fichier contient les idées d'évolution en cours de réflexion ou en discussion.

Une fois validées et priorisées, ces évolutions sont déplacées dans [ROADMAP.md](./ROADMAP.md).

---

## compléments pour la mise en place des tenants

Lors de la mise en place des tenants pour avoir des comptes sociétés différents, il faudrait :
- que chaque société puisse utiliser une base de données séparée pour ses propres données pour rester cloisonnée avec les autres
- que chaque module puisse être débrayable via de la configuration (par exemple, je ne veux pas la facturation)
  Evaluer l'impact, la faisabilité et l'intégration dans le sprint associé. Si la mise en place est trop complexe, répartir en étape sur plusieurs sprints/phases
  Lors du passage en tenant, il faudrait que chaque client puisse configurer sa propre clé d'IA parmi les connecteurs en place (Anthropic, OpenAI, Gemini).
  Si plusieurs clés sont actives, il faudrait que l'administrateur du tenant puisse choisir la clé à privilégier

---
## refonte des pages publiques.

revoir les pages publiques pour mettre en scène Unit404.
Passer le thème en light plutôt qu'en dark.
revoir les textes pour matcher avec le thème de l'application sans rentrer dans les sujets de conception techniques
mettre en place un petit bandeau spécifiant que le projet est en cours de lancement mais pas encore actif
mettre les vraies images
ajouter une entrée "intégrateurs" qui permettrait d'accéder à la documentation de l'api
indiquer que l'achitecture cloud du service est hébergé intégralement en Europe (RGPD compliant)


## revoir la cohérence du parcours

pour chaque utilisateur, vérifier les pages présentes et utilisables en fonction de ses droits.

## evolutions des collaborateurs/utilisateurs

pour pouvoir vérifier l'ensemble des droits utilisateurs, il faudrait une commande pour créer à minima un utilisateur par profil/role
il faudrait pouvoir fusionner les formulaires utilisateurs et collaborateurs. 

## finaliser la mise en place du backoffice

revoir l'accès au lien (seuls les administrateurs ont accès et ils devraient pouvoir y accéder depuis le header et non pas dasn le menu latéral)
revoir les éléments présents dans le backoffice et ceux présents dans l'application

## cohérence ux

revoir les pages existantes pour ne conserver que les pages utiles

## prévoir l'intégration d'un PSP pour le paiement de l'abonnement 


## qualité technique

revoir la séparation MVC notamment l'absence de requetes dans les controlleurs (mais déportées dans les repositories)

nettoyage et rangement de l'ensemble des templates (ils commencent à être nombreux)

## contenu des pages de features (publiques)

les features doivent montrer :
- l'utilisation de l'IA dans le quotidien et dans chaque outil
- la conformité RGPD
- l'anticipation de conformité avec la loi sur la facturation electronique
- les connecteurs avec les outils principaux du marché pour la gestion de projet, le CRM, les outils comptables.

le design du site public doit évoluer ou repartir de 0.
Il doit etre à l'image de Unit404, c'est à dire en version light, utilisant l'image de Unit404 comme mascotte un peu partout

---



Toutes les évolutions ont été intégrées dans la roadmap. Consultez [ROADMAP.md](./ROADMAP.md) pour voir la planification complète.
