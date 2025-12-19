Ce document représente une évolution de l'application en cours. Il va décrire comment faire évoluer les écrans déjà en place

# Liste des projets

ajouter un filtre sur la liste permettant de voir tous les projets ouverts et actifs entre 2 dates (l'année courante par défaut)
pourrais-tu ajouter les filtres suivants :
- par type de projet
- par statut
- par technologie
- par catégorie

la liste affichée soit paginée avec un nombre de résultats par page de (20, 50, 100)

Dans cet écran, il faudrait ajouter des blocs de chiffres en entête de page montrant :
- le chiffre d'affaire total sur la période filtrée sur la liste des projets
- La marge brute sur la période (en pourcentage et en euros)
- Le taux journalier réel utilisé sur le projet sur la période
- le coût homme total sur le projet sur la période
- la marge nette sur la période (en pourcentage et en euros)
- La somme totale des achats portés par les projets sur la période

Formules à utiliser :
- Marge Brute : c'est la différence entre le chiffre d'affaire et les dépenses (achats et dépenses)
- Marge Nette : C'est la marge brute - les couts de l'équipe (cout homme total). Le tout divisé par le chiffre d'affaire

# Détail d'un projet

Dans cet écran il faudrait montrer dans les encarts de chiffres :
- la sommes des temps passés sur le projet / la somme totale des temps à passer sur le projet (révisée avec les déclaration de reste à faire)
- Le budget consommé sur le projet / Le budget total sur le projet
- La somme des coûts du projet
- La marge brute en euros sur le projet (avec sa tendance et sa représentation en pourcentage en pills verte si supérieur à 25%, orange si entre 15 et 25% et rouge en dessous)
- afficher un graphique représentant la consommation du projet dans le temps (par semaines ou par mois) montrant une ligne horizontale décrivant le budget du projet, une courbe montrant le budget consommé et une courbe montrant le budget prévisionnel à consommer
- afficher un graphique sous forme de donut montrant la répartition du budget total entre la marge, les achats et le cout homme

# Contributeurs dans l'application (priorité haute)

Il faudrait renommer "contributeur" par "collaborateur" dans l'application (l'entité peut rester telle quelle pour éviter les regressions de code)

# homepage > dashboard direction

Le bloc devis en attente ne s'affiche pas comme il faut, il faudrait l'afficher de la meme maniere que le dashboard commercial de la home

# dashboard commercial (priorité haute)

voir l'évolution du taux de conversion pour les commerciaux. Il est calculé entre les devis signés vs les devis perdus

prévoir un graphique montrant sur 3 axes : 
- le temps sur l'année en abscisse (mois par mois pourrait suffire)
- l'évolution du CA signé (ordonnée 1 en courbe et k€)
- le volume de  devis créés sur le mois (ordonnée 2 en histogramme et en k€)

# créer une app mobile (au moins PWA) (priorité basse)

Permettant de saisir des temps, de vois ses temps passés sur la semaine, de voir les temps restant à passer
l'application devra permettre ensuite des fonctionnalités supplémentaires, mais pour le moment on reste sur quelque chose de simple
l'utilisateur devra pouvoir se connecter avec le même compte que le hotones

# technique : designer les pages d'erreurs

## pages d'erreur
Il faudrait utiliser le thème pour les pages d'erreurs (à minima 5xx et 4xx) en s'inspirant des templates pages-404.html.twig et pages-500.html.twig en les adaptant aux adaptations du thème actuel

## augmentation de la couverture des tests
Il faudrait arriver à une couverture de tests automatisés de 80%

